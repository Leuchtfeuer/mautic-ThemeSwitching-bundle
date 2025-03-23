Mautic.initSelectTheme = function (themeField) {
    console.log("[Plugin] Custom theme switching logic loaded");

    mQuery('.select-theme-link').click(function (e) {
        e.preventDefault();
        const theme = mQuery(this).data('theme');

        // Get email ID
        let emailId = mQuery('#builder_entity_id').val();
        if (!emailId) {
            const pathParts = window.location.pathname.split('/');
            emailId = pathParts[pathParts.indexOf('edit') + 1]; // Direct extraction
        }

        if (!theme || !emailId) {
            alert("Missing theme or email ID");
            return;
        }

        if (confirm("Use Leuchtfeuer theme switching?")) {
            // 🔥 Critical: Add usePluginMerge=true to trigger your service
            window.location.href = `/s/emails/builder/${emailId}?template=${theme}&original=${emailId}&usePluginMerge=true`;
        }
    });
};

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
