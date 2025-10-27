/********************************************************************
 * 1) Keep reference to Mautic's original initSelectTheme function. *
 ********************************************************************/
var defaultInitSelectTheme = Mautic.initSelectTheme || function() {};

/************************************************************************
 * 2) Override Mautic.initSelectTheme to show our custom dialog first.  *
 ************************************************************************/
Mautic.initSelectTheme = function(themeField) {
    // ---- (a) Do NOT call defaultInitSelectTheme(themeField) yet ----
    // Because that would attach Mautic’s default click handler right away,
    // causing the default "You will lose content" warning to appear first.
    //
    // Instead, we remove the default event so we can show our custom dialog first.

    // !!! Those steps were taken from app/bundles/CoreBundle/Assets/js/4.builder.js

    // --- Fresh email? Do core's default content setup, since we skip Mautic's handler ---
    var customHtml = mQuery('textarea.builder-html');
    var isNew = Mautic.isNewEntity('#page_sessionId, #emailform_sessionId');
    Mautic.showChangeThemeWarning = true;
    Mautic.builderTheme = themeField.val();

    if (isNew) {
        Mautic.showChangeThemeWarning = false;

        // Populate default content
        if (!customHtml.length || !customHtml.val().length) {
            Mautic.setThemeHtml(Mautic.builderTheme);
        }
    }



    const $links = mQuery('.theme-list .select-theme-link');

    // 2a) Remove ALL existing click handlers (including Mautic's default).
    $links.off('click');

    // 2b) Bind our custom “Which approach?” confirm.
    $links.on('click.leuchtfeuerChoice', function(e) {
        e.preventDefault();
        const $link = mQuery(this);
        const theme = $link.data('theme');
        if (!theme) {
            alert("No theme specified.");
            return;
        }

        // // Show our custom modal dialog
        // showThemeSwitchModal(themeField, theme, $link);

        const emailId = getEmailIdFromDomOrUrl();
        if (!emailId) {
            // No email ID (first save), so use Mautic default theme picker
            defaultInitSelectTheme(themeField);
            $link.off('click.leuchtfeuerChoice').trigger('click');
            return;
        }


        fetchEmailTemplate(emailId).then(response => {
            if (response.isCodemode) {
                // Fall back to Mautic default behavior
                defaultInitSelectTheme(themeField);
                $link.off('click.leuchtfeuerChoice').trigger('click');
                return;
            }

            // Otherwise, show our custom modal
            showThemeSwitchModal(themeField, theme, $link);
        });



    });
};

/**
 * Display a minimal modal with 3 theme switching options.
 */
function showThemeSwitchModal(themeField, theme, $link) {
    if (!document.getElementById('themeSwitchModal')) {
        const modal = document.createElement('div');
        modal.id = 'themeSwitchModal';
        modal.innerHTML = `
        <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:9999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(3px);">
            <div style="background:#ffffff;padding:24px 28px;border-radius:10px;text-align:center;min-width:380px;box-shadow:0 8px 24px rgba(0,0,0,0.15);font-family:system-ui, sans-serif;">
                <h3 style="margin-top:0;margin-bottom:14px;color:#3b3f5c;font-size:18px;font-weight:600;">🎨 Theme Switch</h3>
                <div style="display:flex;align-items:flex-start;justify-content:center;gap:8px;margin-bottom:10px;">
                  <span style="color:#4e73df;font-size:15px;line-height:1;">
                    <svg width="16" height="16" style="vertical-align:middle;opacity:0.9;" viewBox="0 0 24 24" fill="none">
                      <circle cx="12" cy="12" r="12" fill="#4e73df" fill-opacity="0.13"/>
                      <path d="M12 7.5a1 1 0 110-2 1 1 0 010 2zm.85 3.15v5.1a.85.85 0 11-1.7 0v-5.1a.85.85 0 111.7 0z" fill="#4e73df"/>
                    </svg>
                  </span>
                  <span style="color:#888;font-size:12px;opacity:0.80;line-height:1.4;">
                    <strong>Note:</strong> Please <u>save your email</u> before using Smart Merge or Translation Mode.<br>
                    Only the <b>last saved version</b> will be merged.
                  </span>
                </div>

                <p style="margin-bottom:20px;color:#555;font-size:14px;">How would you like to apply the new theme?</p>
                
                <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
                    <button id="mergeBtn" style="padding:10px 14px;border:none;border-radius:6px;background:#4e73df;color:white;font-weight:500;font-size:14px;cursor:pointer;transition:all 0.2s;">🔧 Smart Merge</button>
                    <button id="translationBtn" style="padding:10px 14px;border:none;border-radius:6px;background:#36b9cc;color:white;font-weight:500;font-size:14px;cursor:pointer;transition:all 0.2s;">🌐 Translation Mode</button>
                    <button id="mauticBtn" style="padding:10px 14px;border:none;border-radius:6px;background:#f6c23e;color:white;font-weight:500;font-size:14px;cursor:pointer;transition:all 0.2s;">🧼 Mautic Default</button>
                </div>
    
                <button id="cancelBtn" style="padding:8px 12px;border:1px solid #ccc;border-radius:6px;background:transparent;color:#555;cursor:pointer;font-size:13px;transition:all 0.2s;">❌ Cancel</button>
            </div>
        </div>
    `;


        document.body.appendChild(modal);

        // Hover effects
        const hoverStyle = document.createElement('style');
        hoverStyle.innerHTML = `
            #themeSwitchModal button:hover {
                filter: brightness(1.08);
                transform: translateY(-1px);
            }
            #themeSwitchModal button:active {
                filter: brightness(0.95);
                transform: scale(0.98);
            }
        `;
        document.head.appendChild(hoverStyle);

        // Hook up modal buttons
        mQuery('#mergeBtn').on('click', () => {
            launchCustomSwitch(theme, false);
            modal.remove();
        });

        // **Minimal change**: ask for target language and pass to PHP via URL
        mQuery('#translationBtn').on('click', () => {
            var lang = window.prompt('Target language code (e.g., DE, EN-GB):', 'DE');
            if (lang === null) { // cancelled
                return;
            }
            lang = (lang || '').trim().toUpperCase();
            launchCustomSwitch(theme, true, lang);
            modal.remove();
        });

        mQuery('#mauticBtn').on('click', () => {
            modal.remove();
            defaultInitSelectTheme(themeField);
            // Temporarily disable your handler for just this click
            $link.off('click.leuchtfeuerChoice');
            defaultInitSelectTheme(themeField); // triggers Mautic default logic
            $link.trigger('click');

            // Now, after the theme has changed (maybe using a setTimeout or MutationObserver),
            // re-attach your handler so your modal shows again on the next click.
            setTimeout(() => {
                // (Re-)attach your handler again for future clicks
                Mautic.initSelectTheme(themeField);
            }, 1000); // You may adjust the delay if needed

        });

        mQuery('#cancelBtn').on('click', () => {
            modal.remove();
        });
    }
}


/**
 * Redirect to the builder with the appropriate parameters.
 * (Minimal change: optional targetLang param)
 */
function launchCustomSwitch(theme, useTranslation, targetLang) {
    const emailId = getEmailIdFromDomOrUrl();
    if (!emailId) {
        alert("Unable to find email ID.");
        return;
    }
    const url = `/s/emails/builder/${emailId}`
        + `?template=${theme}&original=${emailId}`
        + `&usePluginMerge=true`
        + (useTranslation ? '&translationMode=true' : '')
        + (targetLang ? `&targetLang=${encodeURIComponent(targetLang)}` : '');

    window.location.href = url;
}

/**
 * Helper to get email ID from #builder_entity_id or from URL path (/s/emails/edit/{id}).
 */
function getEmailIdFromDomOrUrl() {
    const possibleId = mQuery('#builder_entity_id').val();
    if (possibleId) {
        return possibleId;
    }
    const parts = window.location.pathname.split('/');
    const editIndex = parts.indexOf('edit');
    if (editIndex !== -1 && parts[editIndex + 1]) {
        return parts[editIndex + 1];
    }
    return null;
}

function fetchEmailTemplate(emailId) {
    return fetch(`/plugin/theme-switch/email-type/${emailId}`)
        .then(resp => {
            if (!resp.ok) {
                throw new Error(`Failed to fetch email info: HTTP ${resp.status}`);
            }
            return resp.json(); // return entire response, not just .template
        })
        .catch(err => {
            console.error('[ThemeSwitch] Could not fetch template info:', err);
            return { isCodemode: false, template: null }; // default fallback
        });
}



// //#####################################################
//
// /********************************************************************
//  * 1) Keep reference to Mautic's original initSelectTheme function. *
//  ********************************************************************/
// var defaultInitSelectTheme = Mautic.initSelectTheme || function() {};
//
// /************************************************************************
//  * 2) Override Mautic.initSelectTheme to show our custom dialog first.  *
//  ************************************************************************/
// Mautic.initSelectTheme = function(themeField) {
//     // ---- (a) Do NOT call defaultInitSelectTheme(themeField) yet ----
//     // Because that would attach Mautic’s default click handler right away,
//     // causing the default "You will lose content" warning to appear first.
//     //
//     // Instead, we remove the default event so we can show our custom dialog first.
//
//     const $links = mQuery('.theme-list .select-theme-link');
//
//     // 2a) Remove ALL existing click handlers (including Mautic's default).
//     $links.off('click');
//
//     // 2b) Bind our custom “Which approach?” confirm.
//     $links.on('click.leuchtfeuerChoice', function(e) {
//         e.preventDefault();
//         const $link = mQuery(this);
//         const theme = $link.data('theme');
//         if (!theme) {
//             alert("No theme specified.");
//             return;
//         }
//
//         // Show your custom confirm FIRST
//         const choice = prompt(
//             "Select Theme Switching Mode:\n" +
//             "1 = Smart Merge (default)\n" +
//             "2 = Translation Mode (keep content, just replace header/footer)\n" +
//             "3 = Mautic Default Behavior (lose all content)"
//         );
//
//         if (choice === "1" || choice === "2") {
//             const useTranslation = (choice === "2");
//             const emailId = getEmailIdFromDomOrUrl();
//             if (!emailId) {
//                 alert("Unable to find email ID.");
//                 return;
//             }
//             const url = `/s/emails/builder/${emailId}`
//                 + `?template=${theme}&original=${emailId}`
//                 + `&usePluginMerge=true`
//                 + (useTranslation ? '&translationMode=true' : '');
//
//             window.location.href = url;
//         } else if (choice === "3") {
//             // Trigger Mautic’s default behavior
//             defaultInitSelectTheme(themeField);
//             $links.off('click.leuchtfeuerChoice');
//             $link.trigger('click');
//         } else {
//             // User cancelled (clicked ESC or Cancel), do nothing
//             return;
//         }
//
//
//
//         if (userWantsPlugin) {
//             // --------------------------------------------------
//             // (1) Plugin Merge Approach
//             // --------------------------------------------------
//             const emailId = getEmailIdFromDomOrUrl();
//             if (!emailId) {
//                 alert("Unable to find email ID for merging.");
//                 return;
//             }
//             // Redirect to builder with your "usePluginMerge=true" param
//             window.location.href = `/s/emails/builder/${emailId}`
//                 + `?template=${theme}&original=${emailId}&usePluginMerge=true`;
//         } else {
//             // --------------------------------------------------
//             // (2) Default Mautic Approach
//             // --------------------------------------------------
//             // a) We call the original Mautic.initSelectTheme NOW
//             //    so it re-attaches Mautic's default handler (which shows “You will lose content?”).
//             defaultInitSelectTheme(themeField);
//
//             // b) Remove our plugin's .leuchtfeuerChoice handler
//             $links.off('click.leuchtfeuerChoice');
//
//             // c) Re-trigger the same click so that Mautic's default confirm runs
//             $link.trigger('click');
//         }
//     });
// };
//
// /**
//  * Helper to get email ID from #builder_entity_id or from URL path (/s/emails/edit/{id}).
//  */
// function getEmailIdFromDomOrUrl() {
//     const possibleId = mQuery('#builder_entity_id').val();
//     if (possibleId) {
//         return possibleId;
//     }
//     const parts = window.location.pathname.split('/');
//     const editIndex = parts.indexOf('edit');
//     if (editIndex !== -1 && parts[editIndex + 1]) {
//         return parts[editIndex + 1];
//     }
//     return null;
// }

//#####################################################


// ################################    Back up javascript, alternative approach for overriding default mautic theme switching behavior

// Mautic.initSelectTheme = function (themeField) {
//     console.log("[Plugin] Custom theme switching logic loaded");
//
//     var customHtml = mQuery('textarea.builder-html');
//     var isNew = Mautic.isNewEntity('#page_sessionId, #emailform_sessionId');
//     Mautic.showChangeThemeWarning = true;
//     Mautic.builderTheme = themeField.val();
//
//     if (isNew) {
//         Mautic.showChangeThemeWarning = false;
//         if (!customHtml.length || !customHtml.val().length) {
//             Mautic.setThemeHtml(Mautic.builderTheme);
//         }
//     }
//
//     mQuery('.select-theme-link').click(function (e) {
//         e.preventDefault();
//         var currentLink = mQuery(this);
//         var theme = currentLink.attr('data-theme');
//         var isCodeMode = (theme === 'mautic_code_mode');
//
//         var useDefault = confirm("Switch theme using default method (OK) or plugin merge (Cancel)?");
//         if (useDefault) {
//             // Default Mautic behavior
//             if (!isCodeMode) {
//                 if (Mautic.showChangeThemeWarning && customHtml.val().length) {
//                     if (!confirm(Mautic.translate('mautic.core.builder.theme_change_warning'))) return;
//                     customHtml.val('');
//                     Mautic.showChangeThemeWarning = false;
//                 }
//             } else {
//                 if (!confirm(Mautic.translate('mautic.core.builder.code_mode_warning'))) return;
//             }
//
//             // Update theme field and UI
//             themeField.val(theme);
//             if (isCodeMode) {
//                 mQuery('.builder').addClass('code-mode');
//                 mQuery('.builder .code-editor, .builder .code-mode-toolbar').removeClass('hide');
//                 mQuery('.builder .builder-toolbar').addClass('hide');
//             } else {
//                 mQuery('.builder').removeClass('code-mode');
//                 mQuery('.builder .code-editor, .builder .code-mode-toolbar').addClass('hide');
//                 mQuery('.builder .builder-toolbar').removeClass('hide');
//                 Mautic.setThemeHtml(theme);
//             }
//
//             // Critical fix: Properly reset all theme UI states
//             mQuery('.theme-list .panel').removeClass('theme-selected');
//             currentLink.closest('.panel').addClass('theme-selected');
//
//             // Reset all selected indicators
//             mQuery('.select-theme-selected').addClass('hide');
//             mQuery('.select-theme-link').removeClass('hide');
//
//             // Set current theme's indicators
//             currentLink.closest('.panel').find('.select-theme-selected').removeClass('hide');
//             currentLink.addClass('hide');
//
//             // Fix: Ensure other theme links remain visible
//             currentLink.closest('.panel').siblings('.panel').find('.select-theme-link').removeClass('hide');
//         } else {
//             // Plugin's custom behavior
//             var emailId = mQuery('#builder_entity_id').val() || window.location.pathname.split('/').pop();
//             if (theme && emailId) {
//                 window.location.href = `/s/emails/builder/${emailId}?template=${theme}&original=${emailId}&usePluginMerge=true`;
//             } else {
//                 alert("Missing required parameters for theme switch.");
//             }
//         }
//     });
// };




// ################################




//
// Mautic.initSelectTheme = function (themeField) {
//     console.log("[Plugin] Custom theme switching logic loaded");
//
//     mQuery('.select-theme-link').click(function (e) {
//         e.preventDefault();
//
//         const theme = mQuery(this).data('theme');
//
//         // Try to get email ID from #builder_entity_id, fallback to URL
//         let emailId = mQuery('#builder_entity_id').val();
//         if (!emailId) {
//             const pathParts = window.location.pathname.split('/');
//             const idFromUrl = pathParts.includes('edit') ? pathParts[pathParts.indexOf('edit') + 1] : null;
//             emailId = idFromUrl;
//         }
//
//         console.log('[Plugin] Clicked theme:', theme);
//         console.log('[Plugin] Resolved emailId:', emailId);
//
//         if (!theme || !emailId) {
//             alert("Missing theme or emailId");
//             return;
//         }
//
//         const confirmed = confirm(
//             'You are using the Theme Switching by Leuchtfeuer plugin. Your current content will be transferred to the new theme.'
//         );
//
//         if (confirmed) {
//             const baseUrl = window.location.origin;
//             const fullUrl = `${baseUrl}/s/emails/builder/${emailId}?template=${theme}&original=${emailId}&usePluginMerge=true`;
//
//             console.log('[Plugin] Redirecting to:', fullUrl);
//             window.location.href = fullUrl;
//         }
//     });
// };

// #######################

// Mautic.initSelectTheme = function (themeField) {
//     alert("Custom plugin code initialized");
//
//     mQuery('[data-theme]').click(function (e) {
//         e.preventDefault();
//
//         alert("Theme was chosen");
//
//         const theme = mQuery(this).data('theme');
//         const templateInput = mQuery(themeField);
//         const customHtml = mQuery('#emailform_customHtml');
//
//         const currentTheme = templateInput.val();
//         const emailId = mQuery('#builder_entity_id').val();
//
//         const baseUrl = window.location.origin;
//         const fullUrl = `${baseUrl}/s/emails/builder/${emailId}?template=${theme}&original=${emailId}&usePluginMerge=true`;
//         window.location.href = fullUrl;
//
//         if (!theme || theme === currentTheme || !emailId) {
//             return;
//         }
//
//         const confirmed = confirm(
//             'You are using the Theme Switching by Leuchtfeuer plugin. Your current content will be transferred to the new theme.'
//         );
//
//
//
//
//         if (confirmed) {
//             // Fallback: manually construct builder URL
//             const baseUrl = window.location.origin;
//             const fullUrl = `${baseUrl}/s/emails/builder/${emailId}?template=${theme}&original=${emailId}&usePluginMerge=true`;
//
//             console.log('[Theme Switch Plugin] Redirecting to:', fullUrl);
//             window.location.href = fullUrl;
//         }
//     });
// };


// ##########################################################################

// Mautic.initSelectTheme = function (themeField) {
//     alert("Custom plugin code initialized");
//
//     mQuery('[data-theme]').click(function (e) {
//         e.preventDefault();
//
//         alert("Theme was chosen");
//
//         const theme = mQuery(this).data('theme');
//         const templateInput = mQuery(themeField);
//         const customHtml = mQuery('#emailform_customHtml');
//
//         const currentTheme = templateInput.val();
//         const emailId = mQuery('#builder_entity_id').val();
//
//         if (!theme || theme === currentTheme || !emailId) {
//             return;
//         }
//
//         const confirmed = confirm(
//             'You are using the Theme Switching by Leuchtfeuer plugin. Your current content will be transferred to the new theme.'
//         );
//
//         if (confirmed) {
//             // Build the builder endpoint URL
//             const redirectUrl = Mautic.generateUrl(`mautic_email_action`, {
//                 objectAction: 'builder',
//                 objectId: emailId,
//             });
//
//             // Include original ID and plugin hint flag
//             const fullUrl = `${redirectUrl}?template=${theme}&original=${emailId}&usePluginMerge=true`;
//
//             // Redirect to trigger backend logic
//             window.location.href = fullUrl;
//         }
//     });
// };
