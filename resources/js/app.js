import './bootstrap';

const dashboardThemeStorageKey = 'mcare-dashboard-theme';

const readDashboardTheme = () => {
    try {
        return window.localStorage.getItem(dashboardThemeStorageKey) === 'dark' ? 'dark' : 'light';
    } catch (error) {
        // Light is the safe default when storage is unavailable or blocked.
        return 'light';
    }
};

const applyDashboardTheme = (theme) => {
    const resolvedTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.dashboardTheme = resolvedTheme;
    document.documentElement.style.colorScheme = resolvedTheme;
};

applyDashboardTheme(readDashboardTheme());

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.remove('dashboard-navigating');

    const sidebar = document.querySelector('[data-dashboard-sidebar]');
    const accountMenus = document.querySelectorAll('[data-dashboard-account]');
    const dashboardLinks = document.querySelectorAll('.dashboard-nav-link, .dashboard-mobile-link');
    const hashLinks = document.querySelectorAll('.dashboard-nav-link[href*="#"], .dashboard-mobile-link[href*="#"]');
    const themeToggleButtons = document.querySelectorAll('[data-dashboard-theme-toggle]');
    const prefetchLinks = document.querySelectorAll('a[data-dashboard-prefetch]');
    const trainingCalendars = document.querySelectorAll('[data-training-calendar]');
    const dashboardMain = document.querySelector('.dashboard-main');
    const protectedViewer = document.querySelector('[data-protected-module-viewer]');
    const securityEventUrl = document.querySelector('meta[name="dashboard-security-event-url"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const desktopDashboardMedia = window.matchMedia('(min-width: 1024px)');
    let navigationLocked = false;
    let navigationUnlockTimer = null;
    let navigationSpamReported = false;

    // Shared native dialogs keep large creation forms outside the normal page flow.
    const dashboardDialogs = document.querySelectorAll('dialog[data-dashboard-dialog]');
    const openDashboardDialog = (dialog) => {
        if (!dialog?.showModal || dialog.open) return;

        dialog.showModal();
        window.requestAnimationFrame(() => {
            dialog.querySelector('[autofocus], input:not([type="hidden"]), select, textarea, button')?.focus();
        });
    };

    document.querySelectorAll('[data-dashboard-dialog-open]').forEach((button) => {
        button.addEventListener('click', () => {
            openDashboardDialog(document.getElementById(button.dataset.dashboardDialogOpen));
        });
    });

    dashboardDialogs.forEach((dialog) => {
        dialog.querySelectorAll('[data-dashboard-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            const bounds = dialog.getBoundingClientRect();
            const outside = event.clientX < bounds.left || event.clientX > bounds.right
                || event.clientY < bounds.top || event.clientY > bounds.bottom;

            if (outside) dialog.close();
        });

        if (dialog.dataset.autoOpen === 'true') {
            openDashboardDialog(dialog);
        }
    });

    document.querySelectorAll('[data-dashboard-dialog-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('[data-action-button]').forEach((button) => {
                button.disabled = true;
                button.classList.add('cursor-not-allowed', 'opacity-70');
                button.textContent = form.dataset.submitLabel || 'Saving...';
            });
        });
    });

    const reportClientSecurityEvent = (eventName) => {
        if (!securityEventUrl || !csrfToken) return;

        window.fetch(securityEventUrl, {
            method: 'POST',
            keepalive: true,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ event: eventName }),
        }).catch(() => {});
    };

    const updateThemeControls = () => {
        const isDark = document.documentElement.dataset.dashboardTheme === 'dark';

        themeToggleButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(isDark));
            const label = button.querySelector('[data-dashboard-theme-label]');
            const moonIcon = button.querySelector('[data-dashboard-theme-icon="moon"]');
            const sunIcon = button.querySelector('[data-dashboard-theme-icon="sun"]');

            if (label) label.textContent = isDark ? 'Light mode' : 'Night mode';
            moonIcon?.classList.toggle('hidden', isDark);
            sunIcon?.classList.toggle('hidden', !isDark);
        });
    };

    themeToggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.dashboardTheme === 'dark' ? 'light' : 'dark';
            applyDashboardTheme(nextTheme);

            try {
                // One shared key carries the user's choice across every role portal.
                window.localStorage.setItem(dashboardThemeStorageKey, nextTheme);
            } catch (error) {
                // The current page can still change theme when storage is disabled.
            }

            updateThemeControls();
        });
    });
    updateThemeControls();
    // Keep the account menu label/icon in sync when another tab changes the
    // shared preference. The page-level storage handler below updates the
    // document attribute; this listener updates controls without a reload.
    window.addEventListener('storage', (event) => {
        if (event.key === dashboardThemeStorageKey) updateThemeControls();
    });

    if (protectedViewer) {
        const notice = protectedViewer.querySelector('[data-protected-viewer-notice]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        let noticeTimer = null;

        const reportRestrictedAction = (eventName) => {
            const url = protectedViewer.dataset.securityEventUrl;
            if (!url || !csrfToken) return;

            window.fetch(url, {
                method: 'POST',
                keepalive: true,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ event: eventName }),
            }).catch(() => {});
        };

        const showRestrictedNotice = (message) => {
            if (!notice) return;
            notice.textContent = message;
            notice.classList.remove('hidden');
            window.clearTimeout(noticeTimer);
            noticeTimer = window.setTimeout(() => notice.classList.add('hidden'), 3500);
        };

        protectedViewer.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            showRestrictedNotice('Right-click is disabled inside the protected module viewer.');
            reportRestrictedAction('context_menu');
        });

        protectedViewer.addEventListener('dragstart', (event) => event.preventDefault());

        document.addEventListener('keydown', (event) => {
            if (!(event.ctrlKey || event.metaKey)) return;
            const key = event.key.toLowerCase();

            if (key === 'p' || key === 's') {
                event.preventDefault();
                showRestrictedNotice(key === 'p'
                    ? 'Printing is disabled for protected learning materials.'
                    : 'Saving protected learning materials is disabled.');
                reportRestrictedAction(key === 'p' ? 'print_shortcut' : 'save_shortcut');
            }
        });

        window.addEventListener('beforeprint', () => {
            document.documentElement.classList.add('protected-module-printing');
            reportRestrictedAction('before_print');
        });
        window.addEventListener('afterprint', () => document.documentElement.classList.remove('protected-module-printing'));
    }

    const syncSidebarAccessibility = () => {
        if (!sidebar) return;

        // Header and collapse controls were removed. The bottom navigation is
        // the mobile entry surface, while the full sidebar remains desktop-only.
        const isInaccessible = ! desktopDashboardMedia.matches;
        sidebar.inert = isInaccessible;
        sidebar.toggleAttribute('aria-hidden', isInaccessible);
    };

    syncSidebarAccessibility();
    desktopDashboardMedia.addEventListener?.('change', syncSidebarAccessibility);

    // Keep the trainee roster compact: opening one learner summary closes the
    // previously opened card while retaining native details keyboard behavior.
    document.querySelectorAll('[data-trainee-accordion]').forEach((accordion) => {
        const traineeCards = Array.from(accordion.querySelectorAll('[data-trainee-card]'));

        traineeCards.forEach((card) => {
            card.addEventListener('toggle', () => {
                if (! card.open) return;

                traineeCards.forEach((otherCard) => {
                    if (otherCard !== card) otherCard.removeAttribute('open');
                });
            });
        });
    });

    // Shared admin/trainer/trainee calendar behavior. Date selection swaps the
    // complete day agenda in place, so sessions never need modal popups.
    trainingCalendars.forEach((calendar) => {
        const dayButtons = Array.from(calendar.querySelectorAll('[data-calendar-day]'));
        const agendaPanels = Array.from(calendar.querySelectorAll('[data-calendar-agenda]'));
        const agenda = calendar.querySelector('.training-calendar-agenda');

        const activateDate = (date, updateHistory = true) => {
            let selectedPanel = null;

            dayButtons.forEach((button) => {
                const isSelected = button.dataset.calendarDate === date;
                button.classList.toggle('is-selected', isSelected);
                button.setAttribute('aria-pressed', String(isSelected));
            });

            agendaPanels.forEach((panel) => {
                const isSelected = panel.dataset.calendarAgenda === date;
                panel.hidden = ! isSelected;
                panel.classList.remove('is-active');
                if (isSelected) selectedPanel = panel;
            });

            // Restart one short panel animation while keeping every event in
            // the selected day visible at the same time.
            if (selectedPanel) {
                window.requestAnimationFrame(() => selectedPanel.classList.add('is-active'));
            }

            if (updateHistory) {
                try {
                    const nextUrl = new URL(window.location.href);
                    nextUrl.searchParams.set('date', date);
                    window.history.replaceState({}, '', nextUrl);
                } catch (error) {
                    // Calendar selection remains fully usable without History API access.
                }
            }
        };

        dayButtons.forEach((button, index) => {
            button.addEventListener('click', () => {
                if (button.dataset.calendarMonthUrl) {
                    window.location.assign(button.dataset.calendarMonthUrl);
                    return;
                }

                activateDate(button.dataset.calendarDate);

                if (window.matchMedia('(max-width: 720px)').matches && agenda) {
                    agenda.scrollIntoView({
                        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                        block: 'start',
                    });
                }
            });

            button.addEventListener('keydown', (event) => {
                const offset = {
                    ArrowLeft: -1,
                    ArrowRight: 1,
                    ArrowUp: -7,
                    ArrowDown: 7,
                }[event.key];

                if (offset === undefined) return;
                const target = dayButtons[index + offset];
                if (! target) return;

                event.preventDefault();
                target.focus();
                if (! target.dataset.calendarMonthUrl) {
                    activateDate(target.dataset.calendarDate);
                }
            });
        });

        activateDate(calendar.dataset.initialDate, false);
    });

    const setActiveNavigationKey = (activeKey) => {
        if (!activeKey) {
            return;
        }

        dashboardLinks.forEach((link) => {
            const isActive = link.dataset.dashboardNavKey === activeKey;

            link.classList.toggle('is-active', isActive);
            if (isActive) {
                link.setAttribute('aria-current', 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    const storedKeyForHash = (hash) => `mcare-dashboard-nav:${window.location.pathname}:${hash}`;

    // Use only one logical item for a hash, even when future placeholder tabs share a section.
    const setActiveHash = () => {
        const activeHash = window.location.hash;

        if (!activeHash) {
            return;
        }

        const matchingLinks = Array.from(hashLinks).filter((link) => {
            const url = new URL(link.href, window.location.href);

            return url.pathname === window.location.pathname && url.hash === activeHash;
        });

        if (matchingLinks.length === 0) {
            return;
        }

        const rememberedKey = window.sessionStorage.getItem(storedKeyForHash(activeHash));
        const rememberedLink = matchingLinks.find((link) => link.dataset.dashboardNavKey === rememberedKey);
        const activeKey = rememberedLink?.dataset.dashboardNavKey ?? matchingLinks[0].dataset.dashboardNavKey;

        setActiveNavigationKey(activeKey);
    };

    let dashboardScrollFrame = null;
    const scrollDashboardTo = (target) => {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const startPosition = window.scrollY;
        const targetPosition = Math.max(0, target.getBoundingClientRect().top + startPosition - 96);
        const distance = targetPosition - startPosition;

        if (reduceMotion || Math.abs(distance) < 8) {
            window.scrollTo({ top: targetPosition, left: 0 });
            return;
        }

        if (dashboardScrollFrame) {
            window.cancelAnimationFrame(dashboardScrollFrame);
        }

        document.documentElement.classList.add('dashboard-scroll-in-progress');
        const duration = 240;
        let startedAt = null;
        const animate = (timestamp) => {
            startedAt ??= timestamp;
            const progress = Math.min((timestamp - startedAt) / duration, 1);
            const easedProgress = 1 - Math.pow(1 - progress, 3);

            window.scrollTo({ top: startPosition + distance * easedProgress, left: 0 });

            if (progress < 1) {
                dashboardScrollFrame = window.requestAnimationFrame(animate);
            } else {
                dashboardScrollFrame = null;
                document.documentElement.classList.remove('dashboard-scroll-in-progress');
            }
        };

        dashboardScrollFrame = window.requestAnimationFrame(animate);
    };

    hashLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const url = new URL(link.href, window.location.href);
            const activeKey = link.dataset.dashboardNavKey;

            if (activeKey && url.hash) {
                window.sessionStorage.setItem(storedKeyForHash(url.hash), activeKey);
                setActiveNavigationKey(activeKey);
            }

            const target = url.hash ? document.getElementById(url.hash.slice(1)) : null;
            const isCurrentDocument = url.pathname === window.location.pathname && url.search === window.location.search;

            // Same-page dashboard tabs scroll smoothly without a full refresh or white blink.
            if (target && isCurrentDocument) {
                event.preventDefault();
                window.history.pushState({}, '', url.hash);
                scrollDashboardTo(target);
            }
        });
    });

    window.addEventListener('hashchange', setActiveHash);
    window.addEventListener('popstate', setActiveHash);
    setActiveHash();

    const prefetchedUrls = new Set();
    const prefetchDashboardPage = (link) => {
        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin || url.href === window.location.href || prefetchedUrls.has(url.href)) {
            return;
        }

        prefetchedUrls.add(url.href);
        const hint = document.createElement('link');
        hint.rel = 'prefetch';
        hint.href = url.href;
        hint.as = 'document';
        document.head.append(hint);
    };

    prefetchLinks.forEach((link) => {
        link.addEventListener('pointerenter', () => prefetchDashboardPage(link), { once: true });
        link.addEventListener('focus', () => prefetchDashboardPage(link), { once: true });
        link.addEventListener('touchstart', () => prefetchDashboardPage(link), { once: true, passive: true });

        link.addEventListener('click', (event) => {
            const url = new URL(link.href, window.location.href);
            const isModifiedClick = event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
            const isSameDocumentHash = url.pathname === window.location.pathname
                && url.search === window.location.search
                && url.hash;

            if (isModifiedClick || url.origin !== window.location.origin || isSameDocumentHash) {
                return;
            }

            if (link.dataset.dashboardNavKey) {
                setActiveNavigationKey(link.dataset.dashboardNavKey);
            }

            // A quick double-click (or a mobile tap burst) can otherwise queue
            // multiple full page requests. Allow the first navigation to win,
            // then release the lock automatically if a connection stalls.
            if (navigationLocked) {
                event.preventDefault();
                if (!navigationSpamReported) {
                    navigationSpamReported = true;
                    reportClientSecurityEvent('navigation_spam');
                }
                return;
            }

            navigationLocked = true;
            document.querySelectorAll('a[data-dashboard-prefetch]').forEach((navLink) => {
                navLink.classList.add('is-loading');
                navLink.setAttribute('aria-disabled', 'true');
            });
            window.clearTimeout(navigationUnlockTimer);
            navigationUnlockTimer = window.setTimeout(() => {
                navigationLocked = false;
                document.querySelectorAll('a[data-dashboard-prefetch]').forEach((navLink) => {
                    navLink.classList.remove('is-loading');
                    navLink.removeAttribute('aria-disabled');
                });
            }, 5000);

            document.documentElement.classList.add('dashboard-navigating');
            dashboardMain?.setAttribute('aria-busy', 'true');
        });
    });

    // Use one accessible confirmation surface for destructive actions and
    // timed quiz starts/submissions instead of browser-specific confirm boxes.
    const confirmDialog = document.querySelector('[data-lms-confirm-dialog]');
    const confirmMessage = confirmDialog?.querySelector('[data-lms-confirm-message]');
    let pendingConfirmedForm = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true' || !confirmDialog?.showModal) {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            pendingConfirmedForm = form;
            if (confirmMessage) {
                confirmMessage.textContent = form.dataset.confirm || 'Continue with this action?';
            }
            confirmDialog.showModal();
        });
    });

    confirmDialog?.addEventListener('close', () => {
        const form = pendingConfirmedForm;
        pendingConfirmedForm = null;

        if (confirmDialog.returnValue !== 'confirm' || !form) return;
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });

    // Batch and single-trainee assignment share the same form component.
    // Disable the inactive selector so only the chosen audience reaches Laravel.
    document.querySelectorAll('[data-audience-scope]').forEach((scope) => {
        const controls = Array.from(scope.querySelectorAll('[data-audience-control]'));
        const batchSelect = scope.querySelector('[data-audience-batch]');
        const traineeSelect = scope.querySelector('[data-audience-trainee]');

        const syncAudience = () => {
            const selectedType = controls.find((control) => control.checked)?.value || 'batch';
            if (batchSelect) batchSelect.disabled = selectedType !== 'batch';
            if (traineeSelect) traineeSelect.disabled = selectedType !== 'trainee';
        };

        controls.forEach((control) => control.addEventListener('change', syncAudience));
        syncAudience();
    });

    // Show the selected file immediately. This is deliberately lightweight:
    // it previews file identity and size without reading large videos/PDFs.
    document.querySelectorAll('[data-lms-file-input]').forEach((input) => {
        const picker = input.closest('.lms-file-picker') || input.parentElement;
        const preview = picker?.querySelector('[data-lms-file-preview]');
        const activatePicker = () => input.click();

        preview?.addEventListener('click', activatePicker);
        preview?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            activatePicker();
        });
        if (preview) {
            preview.tabIndex = 0;
            preview.setAttribute('role', 'button');
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file || !preview) return;

            const sizeInMb = file.size / (1024 * 1024);
            const strong = preview.querySelector('strong');
            const small = preview.querySelector('small');
            if (strong) strong.textContent = file.name;
            if (small) small.textContent = `${sizeInMb.toFixed(sizeInMb >= 10 ? 0 : 1)} MB - ready to upload`;
        });
    });

    document.querySelectorAll('[data-close-inline-editor]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('.lms-inline-editor')?.removeAttribute('open');
        });
    });

    document.querySelectorAll('.lms-inline-editor').forEach((editor) => {
        editor.addEventListener('toggle', () => {
            if (!editor.open) return;
            window.requestAnimationFrame(() => {
                editor.querySelector('form input:not([type="hidden"]), form textarea, form select')?.focus();
            });
        });
    });

    // Dynamic quiz builder. Every reindex keeps nested input names aligned
    // with Laravel's questions[n][options][n] validation contract.
    document.querySelectorAll('[data-quiz-builder]').forEach((builder) => {
        const questionList = builder.querySelector('[data-quiz-question-list]');
        const questionTemplate = builder.querySelector('[data-quiz-question-template]');
        const addQuestionButton = builder.querySelector('[data-add-question]');

        if (!questionList || !questionTemplate) return;

        const updateOptionControls = (question) => {
            const type = question.querySelector('[data-question-type]')?.value || 'multiple_choice';
            const optionList = question.querySelector('[data-quiz-option-list]');
            const addOptionButton = question.querySelector('[data-add-option]');
            if (!optionList) return;

            if (type === 'true_false') {
                optionList.innerHTML = ['True', 'False'].map((label) => `
                    <div class="lms-option-row" data-quiz-option>
                        <span class="lms-option-letter" aria-hidden="true"></span>
                        <label class="sr-only"></label>
                        <input value="${label}" readonly required>
                        <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option" hidden>x</button>
                    </div>
                `).join('');
                if (addOptionButton) addOptionButton.hidden = true;
            } else {
                const options = Array.from(optionList.querySelectorAll('[data-quiz-option]'));
                if (options.length < 2) {
                    while (optionList.querySelectorAll('[data-quiz-option]').length < 4) {
                        optionList.insertAdjacentHTML('beforeend', `
                            <div class="lms-option-row" data-quiz-option>
                                <span class="lms-option-letter" aria-hidden="true"></span>
                                <label class="sr-only"></label>
                                <input required>
                                <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option">x</button>
                            </div>
                        `);
                    }
                }
                optionList.querySelectorAll('input').forEach((option) => option.readOnly = false);
                optionList.querySelectorAll('[data-remove-option]').forEach((button) => button.hidden = false);
                if (addOptionButton) addOptionButton.hidden = false;
            }
        };

        const reindexBuilder = () => {
            const questions = Array.from(questionList.querySelectorAll('[data-quiz-question]'));

            questions.forEach((question, questionIndex) => {
                question.dataset.questionIndex = String(questionIndex);
                const number = question.querySelector('.lms-question-number');
                if (number) number.textContent = `Question ${questionIndex + 1}`;

                question.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/questions\[(?:\d+|__INDEX__)\]/, `questions[${questionIndex}]`);
                });

                question.querySelectorAll('[id]').forEach((field) => {
                    field.id = field.id.replace(/question-(?:\d+|__INDEX__)-/, `question-${questionIndex}-`);
                });

                const options = Array.from(question.querySelectorAll('[data-quiz-option]'));
                const correctSelect = question.querySelector('[data-correct-option]');
                const previousCorrect = Number(correctSelect?.value || 0);

                options.forEach((option, optionIndex) => {
                    const optionInput = option.querySelector('input');
                    const optionLabel = option.querySelector('label');
                    const optionLetter = option.querySelector('.lms-option-letter');
                    const removeButton = option.querySelector('[data-remove-option]');
                    const letter = String.fromCharCode(65 + optionIndex);

                    if (optionInput) {
                        optionInput.name = `questions[${questionIndex}][options][${optionIndex}]`;
                        optionInput.id = `question-${questionIndex}-option-${optionIndex}`;
                    }
                    if (optionLabel) {
                        optionLabel.htmlFor = optionInput?.id || '';
                        optionLabel.textContent = `Option ${optionIndex + 1}`;
                    }
                    if (optionLetter) optionLetter.textContent = letter;
                    if (removeButton) removeButton.setAttribute('aria-label', `Remove option ${optionIndex + 1}`);
                });

                if (correctSelect) {
                    correctSelect.name = `questions[${questionIndex}][correct_option]`;
                    correctSelect.id = `question-${questionIndex}-correct`;
                    correctSelect.innerHTML = options.map((_, optionIndex) => {
                        const selected = optionIndex === Math.min(previousCorrect, options.length - 1) ? ' selected' : '';
                        return `<option value="${optionIndex}"${selected}>Option ${String.fromCharCode(65 + optionIndex)}</option>`;
                    }).join('');
                }
            });
        };

        const bindQuestion = (question) => {
            const typeSelect = question.querySelector('[data-question-type]');
            const addOptionButton = question.querySelector('[data-add-option]');

            typeSelect?.addEventListener('change', () => {
                updateOptionControls(question);
                reindexBuilder();
            });

            addOptionButton?.addEventListener('click', () => {
                const optionList = question.querySelector('[data-quiz-option-list]');
                const optionCount = optionList?.querySelectorAll('[data-quiz-option]').length || 0;
                if (!optionList || optionCount >= 6) return;

                optionList.insertAdjacentHTML('beforeend', `
                    <div class="lms-option-row" data-quiz-option>
                        <span class="lms-option-letter" aria-hidden="true"></span>
                        <label class="sr-only"></label>
                        <input required>
                        <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option">x</button>
                    </div>
                `);
                reindexBuilder();
            });

            question.addEventListener('click', (event) => {
                const removeOptionButton = event.target.closest('[data-remove-option]');
                if (removeOptionButton) {
                    const options = question.querySelectorAll('[data-quiz-option]');
                    if (options.length <= 2) return;
                    removeOptionButton.closest('[data-quiz-option]')?.remove();
                    reindexBuilder();
                    return;
                }

                if (event.target.closest('[data-remove-question]')) {
                    const questions = questionList.querySelectorAll('[data-quiz-question]');
                    if (questions.length <= 1) {
                        question.querySelector('textarea')?.focus();
                        return;
                    }
                    question.remove();
                    reindexBuilder();
                }
            });

            updateOptionControls(question);
        };

        questionList.querySelectorAll('[data-quiz-question]').forEach(bindQuestion);

        addQuestionButton?.addEventListener('click', () => {
            const questionIndex = questionList.querySelectorAll('[data-quiz-question]').length;
            const wrapper = document.createElement('div');
            wrapper.innerHTML = questionTemplate.innerHTML
                .replaceAll('__INDEX__', String(questionIndex))
                .replaceAll('__NUMBER__', String(questionIndex + 1))
                .trim();
            const question = wrapper.firstElementChild;
            if (!question) return;

            questionList.append(question);
            bindQuestion(question);
            reindexBuilder();
            question.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'center',
            });
            question.querySelector('textarea')?.focus({ preventScroll: true });
        });

        reindexBuilder();
    });

    // Quiz attempts retain an authoritative server deadline. The client timer
    // is a readable countdown only; the backend still enforces expiration.
    document.querySelectorAll('[data-quiz-attempt]').forEach((attemptPage) => {
        const form = attemptPage.querySelector('[data-quiz-attempt-form]');
        const timer = attemptPage.querySelector('[data-quiz-timer]');
        const timerValue = attemptPage.querySelector('[data-quiz-timer-value]');
        const progress = attemptPage.querySelector('[data-answer-progress]');
        const submitButton = attemptPage.querySelector('[data-submit-quiz]');
        const questions = Array.from(attemptPage.querySelectorAll('[data-answer-question]'));
        const jumps = Array.from(attemptPage.querySelectorAll('[data-question-jump]'));
        const remainingValue = attemptPage.dataset.remainingSeconds;
        let remainingSeconds = remainingValue === 'unlimited' ? null : Number(remainingValue || 0);
        let autoSubmitted = false;

        const updateAnswerProgress = () => {
            const answered = questions.filter((question) => question.querySelector('input[type="radio"]:checked')).length;
            if (progress) progress.textContent = `${answered} of ${questions.length} answered`;
            questions.forEach((question, index) => {
                jumps[index]?.classList.toggle('is-answered', Boolean(question.querySelector('input[type="radio"]:checked')));
            });
        };

        const formatTime = (seconds) => {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainder = seconds % 60;
            return hours > 0
                ? `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`
                : `${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`;
        };

        const tick = () => {
            if (remainingSeconds === null) {
                timer?.classList.remove('is-warning', 'is-critical');
                return;
            }

            if (timerValue) timerValue.textContent = formatTime(Math.max(0, remainingSeconds));
            timer?.classList.toggle('is-warning', remainingSeconds > 60 && remainingSeconds <= 300);
            timer?.classList.toggle('is-critical', remainingSeconds <= 60);

            if (remainingSeconds <= 0) {
                if (!autoSubmitted && form) {
                    autoSubmitted = true;
                    form.dataset.confirmed = 'true';
                    form.requestSubmit();
                }
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Finalizing...';
                }
                return;
            }

            remainingSeconds -= 1;
            window.setTimeout(tick, 1000);
        };

        form?.addEventListener('change', updateAnswerProgress);
        updateAnswerProgress();
        tick();
    });

    // Native details menus remain keyboard-friendly; close only when focus/click moves away.
    document.addEventListener('click', (event) => {
        accountMenus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        accountMenus.forEach((menu) => menu.removeAttribute('open'));
        document.querySelectorAll('.lms-inline-editor[open]').forEach((editor) => editor.removeAttribute('open'));
    });
});

window.addEventListener('pageshow', () => {
    applyDashboardTheme(readDashboardTheme());
    document.documentElement.classList.remove('dashboard-navigating');
    document.querySelector('.dashboard-main')?.removeAttribute('aria-busy');
    document.querySelectorAll('a[data-dashboard-prefetch].is-loading').forEach((navLink) => {
        navLink.classList.remove('is-loading');
        navLink.removeAttribute('aria-disabled');
    });
});

window.addEventListener('storage', (event) => {
    if (event.key === dashboardThemeStorageKey) {
        applyDashboardTheme(readDashboardTheme());
    }

});
