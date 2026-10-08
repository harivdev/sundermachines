<?php require_once(__DIR__ . "/camera_modal.php"); ?>
    <script>

        const menuToggle = document.getElementById("menuToggle");
        const mainMenu = document.getElementById("mainMenu");
        if(menuToggle && mainMenu) {
            menuToggle.addEventListener("click", function(e) {
                e.stopPropagation();
                mainMenu.classList.toggle("open");
            });
        }

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

                if (target.classList.contains('item-search-input') && ['ArrowDown', 'ArrowUp', 'PageDown', 'PageUp'].includes(e.key)) return;

                if (e.key === 'Enter' || e.key === 'ArrowDown') {
                    if (target.tagName === 'SELECT' && e.key === 'ArrowDown') return;
                    if (target.classList.contains('item-search-input') && e.key === 'ArrowDown') return;
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

        // Minimal smooth navigation prefetch: triggers on hover/touch for instant page loading
        (function() {
            if (!('fetch' in window)) return;
            const prefetched = new Set();
            function prefetch(url) {
                if (!url || prefetched.has(url) || url.startsWith('javascript:') || url.startsWith('#') || url.includes('logout') || url.includes('delete')) return;
                prefetched.add(url);
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = url;
                document.head.appendChild(link);
            }
            document.addEventListener('mouseover', function(e) {
                const a = e.target.closest('a');
                if (a && a.href && a.origin === location.origin && !a.target) prefetch(a.href);
            }, { passive: true });
            document.addEventListener('touchstart', function(e) {
                const a = e.target.closest('a');
                if (a && a.href && a.origin === location.origin && !a.target) prefetch(a.href);
            }, { passive: true });
        })();
    </script>
</body>
</html>

