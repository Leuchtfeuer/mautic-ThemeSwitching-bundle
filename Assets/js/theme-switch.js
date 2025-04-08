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

        // Show your custom confirm FIRST
        const choice = prompt(
            "Select Theme Switching Mode:\n" +
            "1 = Smart Merge (default)\n" +
            "2 = Translation Mode (keep content, just replace header/footer)\n" +
            "3 = Mautic Default Behavior (lose all content)"
        );

        if (choice === "1" || choice === "2") {
            const useTranslation = (choice === "2");
            const emailId = getEmailIdFromDomOrUrl();
            if (!emailId) {
                alert("Unable to find email ID.");
                return;
            }
            const url = `/s/emails/builder/${emailId}`
                + `?template=${theme}&original=${emailId}`
                + `&usePluginMerge=true`
                + (useTranslation ? '&translationMode=true' : '');

            window.location.href = url;
        } else if (choice === "3") {
            // Trigger Mautic’s default behavior
            defaultInitSelectTheme(themeField);
            $links.off('click.leuchtfeuerChoice');
            $link.trigger('click');
        } else {
            // User cancelled (clicked ESC or Cancel), do nothing
            return;
        }



        if (userWantsPlugin) {
            // --------------------------------------------------
            // (1) Plugin Merge Approach
            // --------------------------------------------------
            const emailId = getEmailIdFromDomOrUrl();
            if (!emailId) {
                alert("Unable to find email ID for merging.");
                return;
            }
            // Redirect to builder with your "usePluginMerge=true" param
            window.location.href = `/s/emails/builder/${emailId}`
                + `?template=${theme}&original=${emailId}&usePluginMerge=true`;
        } else {
            // --------------------------------------------------
            // (2) Default Mautic Approach
            // --------------------------------------------------
            // a) We call the original Mautic.initSelectTheme NOW
            //    so it re-attaches Mautic's default handler (which shows “You will lose content?”).
            defaultInitSelectTheme(themeField);

            // b) Remove our plugin's .leuchtfeuerChoice handler
            $links.off('click.leuchtfeuerChoice');

            // c) Re-trigger the same click so that Mautic's default confirm runs
            $link.trigger('click');
        }
    });
};

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
