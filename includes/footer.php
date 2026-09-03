<?php require_once(__DIR__ . "/camera_modal.php"); ?>
    <script>
        // MOBILE TOGGLE

        // MOBILE TOGGLE
        const menuToggle = document.getElementById("menuToggle");
        const mainMenu = document.getElementById("mainMenu");
        if(menuToggle && mainMenu) {
            menuToggle.addEventListener("click", function(e) {
                e.stopPropagation();
                mainMenu.classList.toggle("open");
            });
        }

        // GLOBAL KEYBOARD FIELD NAVIGATION ACROSS ENTIRE WEBSITE (ENTER & ARROW KEYS)
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('keydown', function (e) {
                const target = e.target;
                if (!target || !['INPUT', 'SELECT'].includes(target.tagName)) return;
                if (target.type === 'submit' || target.type === 'button' || target.type === 'file' || target.type === 'checkbox' || target.type === 'radio') return;

                const form = target.closest('form');
                if (!form) return;

                const inputs = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([type="file"]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled])'));
                const index = inputs.indexOf(target);
                if (index === -1) return;

                // Enter Key or Down Arrow -> Move to Next Field
                if (e.key === 'Enter' || e.key === 'ArrowDown') {
                    if (target.tagName === 'SELECT' && e.key === 'ArrowDown') return;
                    e.preventDefault();
                    const nextInput = inputs[index + 1];
                    if (nextInput) {
                        nextInput.focus();
                        if (typeof nextInput.select === 'function' && nextInput.tagName === 'INPUT' && nextInput.type === 'text') {
                            nextInput.select();
                        }
                    } else {
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn) submitBtn.focus();
                    }
                }
                // Up Arrow -> Move to Previous Field
                else if (e.key === 'ArrowUp') {
                    if (target.tagName === 'SELECT') return;
                    e.preventDefault();
                    const prevInput = inputs[index - 1];
                    if (prevInput) {
                        prevInput.focus();
                        if (typeof prevInput.select === 'function' && prevInput.tagName === 'INPUT' && prevInput.type === 'text') {
                            prevInput.select();
                        }
                    }
                }
                // Right Arrow -> Move to Next Field (when cursor at end of input string)
                else if (e.key === 'ArrowRight') {
                    if (target.tagName === 'SELECT') return;
                    if (typeof target.selectionEnd === 'number' && target.selectionEnd === target.value.length) {
                        e.preventDefault();
                        const nextInput = inputs[index + 1];
                        if (nextInput) {
                            nextInput.focus();
                            if (typeof nextInput.select === 'function' && nextInput.tagName === 'INPUT' && nextInput.type === 'text') {
                                nextInput.select();
                            }
                        }
                    }
                }
                // Left Arrow -> Move to Previous Field (when cursor at start of input string)
                else if (e.key === 'ArrowLeft') {
                    if (target.tagName === 'SELECT') return;
                    if (typeof target.selectionStart === 'number' && target.selectionStart === 0) {
                        e.preventDefault();
                        const prevInput = inputs[index - 1];
                        if (prevInput) {
                            prevInput.focus();
                            if (typeof prevInput.select === 'function' && prevInput.tagName === 'INPUT' && prevInput.type === 'text') {
                                prevInput.select();
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
