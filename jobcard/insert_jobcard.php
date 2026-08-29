<?php
require_once(__DIR__ . "/../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentYY = date('y');
    $currentMM = date('m');
    $prefix = "{$currentYY}{$currentMM}J";

    // Strip slashes and spaces from input cardNo; enforce YYMMJ prefix
    $cardNo = str_replace(['/', ' '], '', trim($_POST['cardNo'] ?? ''));
    if (empty($cardNo) || strpos($cardNo, $prefix) !== 0) {
        $cardNo = ''; // Re-generate cleanly using YYMMJ00001 format
    }

    // Parse givenDate into YYYY-MM-DD format for MySQL DATE column
    $rawDate = trim($_POST['givenDate'] ?? '');
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $rawDate, $dMatch)) {
        $givenDate = "{$dMatch[3]}-{$dMatch[2]}-{$dMatch[1]}";
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
        $givenDate = $rawDate;
    } else {
        $givenDate = date('Y-m-d');
    }
    $givenDate = mysqli_real_escape_string($conn, $givenDate);

    $customerId = isset($_POST['customerId']) ? intval($_POST['customerId']) : 0;
    $customerPhone = mysqli_real_escape_string($conn, trim($_POST['customerPhone'] ?? ''));
    $customerName = mysqli_real_escape_string($conn, trim($_POST['customerName'] ?? ''));
    $customerCity = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));

    // Customer resolution / auto-create fallback
    if ($customerId > 0) {
        $res = mysqli_query($conn, "SELECT id FROM customer WHERE id = $customerId LIMIT 1");
        if (!$res || mysqli_num_rows($res) === 0) {
            $customerId = 0;
        }
    }

    if ($customerId === 0 && !empty($customerPhone)) {
        $res = mysqli_query($conn, "SELECT id FROM customer WHERE phoneNo1 = '$customerPhone' LIMIT 1");
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $customerId = (int)$row['id'];
        }
    }

    if ($customerId === 0 && (!empty($customerPhone) || !empty($customerName))) {
        // Create basic customer record
        $last_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT customerId FROM customer WHERE customerId LIKE 'C%' ORDER BY id DESC LIMIT 1"));
        $next_num = 1;
        if ($last_row && preg_match('/(\d+)$/', $last_row['customerId'], $m)) {
            $next_num = (int)$m[1] + 1;
        }
        $genCustId = 'C' . str_pad($next_num, 7, '0', STR_PAD_LEFT);

        $now = date('Y-m-d H:i:s');
        $addrSql = "INSERT INTO address (createdOn, modifiedOn, city) VALUES ('$now', '$now', '$customerCity')";
        mysqli_query($conn, $addrSql);
        $addrId = mysqli_insert_id($conn);

        $custSql = "INSERT INTO customer (customerId, active, name, phoneNo1, address) VALUES ('$genCustId', 1, '$customerName', '$customerPhone', $addrId)";
        mysqli_query($conn, $custSql);
        $customerId = (int)mysqli_insert_id($conn);
    }

    $now = date('Y-m-d H:i:s');
    $user = "System Admin";

    // Fallback if cardNo wasn't provided or empty
    if (empty($cardNo)) {
        $cardNo = $_SESSION['draft_jobcard_no'] ?? '';
        $cardNo = str_replace(['/', ' '], '', $cardNo);
    }

    // Atomic Insertion & Concurrency Handling Loop
    $inserted = false;
    $attempts = 0;
    $finalCardNo = $cardNo;
    $jobcardId = 0;

    while (!$inserted && $attempts < 15) {
        $attempts++;
        if (empty($finalCardNo)) {
            $lastSql = "SELECT cardNo FROM jobcard WHERE cardNo IS NOT NULL AND cardNo != '' ORDER BY id DESC LIMIT 100";
            $cardResult = mysqli_query($conn, $lastSql);
            $lastYear = null;
            $lastSeq = 0;
            if ($cardResult) {
                while ($row = mysqli_fetch_assoc($cardResult)) {
                    $cNo = str_replace(['/', ' '], '', trim($row['cardNo'] ?? ''));
                    if (preg_match('/^(\d{2})(\d{2})J(?:C)?(\d{5})$/', $cNo, $m)) {
                        $lastYear = intval($m[1]);
                        $lastSeq = intval($m[3]);
                        break;
                    } elseif (preg_match('/^(\d{2})\/(\d{2})\/J\/(\d+)$/', $cNo, $m)) {
                        $lastYear = intval($m[1]);
                        $lastSeq = intval($m[3]);
                        break;
                    }
                }
            }

            $targetYY = intval($currentYY);
            if ($lastYear !== null && $lastYear === $targetYY) {
                $nextSeq = $lastSeq + 1 + ($attempts > 1 ? ($attempts - 1) : 0);
            } else {
                $nextSeq = 1 + ($attempts > 1 ? ($attempts - 1) : 0);
            }

            $finalCardNo = "{$currentYY}{$currentMM}J" . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
        }

        $e_cardNo = mysqli_real_escape_string($conn, $finalCardNo);

        mysqli_begin_transaction($conn);

        try {
            // Check if cardNo is already taken by another concurrent user
            $checkRes = mysqli_query($conn, "SELECT id FROM jobcard WHERE cardNo = '$e_cardNo' LIMIT 1");
            if ($checkRes && mysqli_num_rows($checkRes) > 0) {
                // Card number exists, retry with next increment
                mysqli_rollback($conn);
                $finalCardNo = '';
                continue;
            }

            // Insert into jobcard table
            $empName = mysqli_real_escape_string($conn, trim($_POST['employeeName'] ?? $_POST['employee'] ?? ''));
            $empIdVal = "NULL";
            if (!empty($empName)) {
                $empRes = mysqli_query($conn, "SELECT id FROM employee WHERE name = '$empName' LIMIT 1");
                if ($empRes && $empRow = mysqli_fetch_assoc($empRes)) {
                    $empIdVal = intval($empRow['id']);
                } else {
                    $nowEmp = date('Y-m-d H:i:s');
                    mysqli_query($conn, "INSERT INTO employee (name, active, createdOn, modifiedOn) VALUES ('$empName', 1, '$nowEmp', '$nowEmp')");
                    $empIdVal = (int)mysqli_insert_id($conn);
                }
            }
            $e_jobCategory = mysqli_real_escape_string($conn, $_POST['jobCategory'] ?? 'Onsite');
            $custVal = ($customerId > 0) ? intval($customerId) : "NULL";

            $jcSql = "INSERT INTO jobcard (cardNo, givenDate, customer, employee, jobStatus, jobCategory, completed, delivered, actualAmountSum, quoteAmountSum, receivedAmountSum, laborCharge, createdBy, createdOn, modifiedBy, modifiedOn) 
                      VALUES ('$e_cardNo', '$givenDate', $custVal, $empIdVal, 'New', '$e_jobCategory', 0, 0, 0, 0, 0, 0, '$user', '$now', '$user', '$now')";

            if (!mysqli_query($conn, $jcSql)) {
                throw new Exception("Error inserting jobcard: " . mysqli_error($conn));
            }

            $jobcardId = mysqli_insert_id($conn);

            // Process photo uploads
            $uploadedPhotos = [];
            $uploadDir = __DIR__ . '/../uploads/jobcards/';
            if (!file_exists($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            if (!empty($_FILES['jobcard_photos']['name'])) {
                $fileNames = is_array($_FILES['jobcard_photos']['name']) ? $_FILES['jobcard_photos']['name'] : [$_FILES['jobcard_photos']['name']];
                $fileTmpNames = is_array($_FILES['jobcard_photos']['tmp_name']) ? $_FILES['jobcard_photos']['tmp_name'] : [$_FILES['jobcard_photos']['tmp_name']];
                $fileSizes = is_array($_FILES['jobcard_photos']['size']) ? $_FILES['jobcard_photos']['size'] : [$_FILES['jobcard_photos']['size']];
                $fileErrors = is_array($_FILES['jobcard_photos']['error']) ? $_FILES['jobcard_photos']['error'] : [$_FILES['jobcard_photos']['error']];

                $forbiddenExts = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'sh', 'bat', 'cgi', 'pl', 'py'];
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                foreach ($fileNames as $fIdx => $fName) {
                    if (isset($fileErrors[$fIdx]) && $fileErrors[$fIdx] === UPLOAD_ERR_OK && !empty($fileTmpNames[$fIdx])) {
                        // File size limit 10MB
                        if (isset($fileSizes[$fIdx]) && $fileSizes[$fIdx] > 10 * 1024 * 1024) continue;

                        $ext = strtolower(pathinfo($fName, PATHINFO_EXTENSION));
                        if (in_array($ext, $forbiddenExts)) continue;

                        if (in_array($ext, $allowedExts)) {
                            // Validate MIME / Image type safely
                            if (function_exists('mime_content_type')) {
                                $mime = @mime_content_type($fileTmpNames[$fIdx]);
                                if ($mime && strpos($mime, 'image/') !== 0) continue;
                            } elseif (function_exists('getimagesize')) {
                                $imgInfo = @getimagesize($fileTmpNames[$fIdx]);
                                if ($imgInfo === false) continue;
                            }

                            $newPhotoName = $finalCardNo . '_' . ($fIdx + 1) . '.' . $ext;
                            if (move_uploaded_file($fileTmpNames[$fIdx], $uploadDir . $newPhotoName)) {
                                $uploadedPhotos[] = 'uploads/jobcards/' . $newPhotoName;
                            }
                        }
                    }
                }
            }

            if (!empty($_POST['jobcard_camera_photos'])) {
                $camPhotos = is_array($_POST['jobcard_camera_photos']) ? $_POST['jobcard_camera_photos'] : [$_POST['jobcard_camera_photos']];
                foreach ($camPhotos as $base64Str) {
                    if (!empty($base64Str) && preg_match('/^data:image\/(\w+);base64,/', $base64Str, $typeMatch)) {
                        $data = substr($base64Str, strpos($base64Str, ',') + 1);
                        $data = base64_decode($data);
                        if ($data !== false) {
                            $ext = strtolower($typeMatch[1]);
                            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) $ext = 'jpg';
                            $camPhotoName = 'jc_' . $jobcardId . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                            if (file_put_contents($uploadDir . $camPhotoName, $data)) {
                                $uploadedPhotos[] = 'uploads/jobcards/' . $camPhotoName;
                            }
                        }
                    }
                }
            }

            $pictureVal = !empty($uploadedPhotos) ? mysqli_real_escape_string($conn, json_encode(array_values(array_unique($uploadedPhotos)))) : '';

            // Insert into jobcarditems table
            $mName = mysqli_real_escape_string($conn, trim($_POST['machineName'] ?? $_POST['machine'] ?? ''));
            $mId = (int)($_POST['machine'] ?? 0);
            if (empty($mName) && $mId > 0) {
                $mRes = mysqli_query($conn, "SELECT machineName FROM machine WHERE id = $mId LIMIT 1");
                if ($mRes && $mRow = mysqli_fetch_assoc($mRes)) {
                    $mName = mysqli_real_escape_string($conn, $mRow['machineName']);
                }
            }
            $serial = mysqli_real_escape_string($conn, $_POST['serial'] ?? '');
            $wDetails = mysqli_real_escape_string($conn, $_POST['workDetails'] ?? 'Service');
            $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');

            $machVal = ($mId > 0) ? $mId : "NULL";
            $itemSql = "INSERT INTO jobcarditems (id, jobCard, machine, machineName, serialNo, issueDetails, remark, picture, actualAmount, quoteAmount, assembledByUs, deleted, createdBy, createdOn, modifiedBy, modifiedOn) 
                        VALUES ($jobcardId, $jobcardId, $machVal, '$mName', '$serial', '$wDetails', '$remarks', '$pictureVal', 0, 0, 0, 0, '$user', '$now', '$user', '$now')";
            
            if (!mysqli_query($conn, $itemSql)) {
                throw new Exception("Error inserting jobcard item: " . mysqli_error($conn));
            }

            mysqli_commit($conn);
            $inserted = true;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $lastErrorMsg = $e->getMessage();
            $finalCardNo = '';
        }
    }

    if ($inserted) {
        // Clear session reservation after successful save
        unset($_SESSION['draft_jobcard_no']);
        unset($_SESSION['draft_jobcard_year']);

        echo "<script>alert('Job Card Created successfully! Number: $finalCardNo'); window.location.href='edit.php?id=$jobcardId';</script>";
    } else {
        echo "Error saving job card: " . htmlspecialchars($lastErrorMsg ?? 'Please try again.');
    }

} else {
    header("Location: create.php");
    exit;
}
?>