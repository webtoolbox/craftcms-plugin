wtbx.setting = {
    checkAllGroups: function(e){
        document.getElementById('settings-user-group-list').classList.add('d_none');
        document.getElementById('settings-all-users').checked = true;
        var checkboxes = document.getElementsByName('settings[user_roles][]');
        if (e.checked) {
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = true;
            }
        }else{
            document.getElementById('settings-no-users').checked = true;
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = false;
            }
        }
    },

    unCheckAllGroups: function(e){
        document.getElementById('settings-user-group-list').classList.add('d_none');
        var checkboxes = document.getElementsByName('settings[user_roles][]');
        if (e.checked) {
            for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = false;
            }
        }
    },

    showUserGroupList: function(){
        var selectedUserGroups = document.getElementById('settings-hidden-user-groups').value;
        var selectedUserGroupArray = selectedUserGroups.split(",");
        var checkboxes = document.getElementsByName('settings[user_roles][]');
        for (var i = 0; i < checkboxes.length; i++) {
            if(selectedUserGroupArray.includes(checkboxes[i].value) != ''){
                checkboxes[i].checked = true;                
            }else{
                checkboxes[i].checked = false;
            }
        }        
        document.getElementById('settings-user-group-list').classList.remove('d_none');
    },

    copyUrl: function(element, textToCopy='') {
        if(textToCopy){
            var copyText = textToCopy;
        }else{
            var communityPage = document.getElementById('settings-community-url');
            if(communityPage && communityPage.value !== ''){
                var copyText = document.getElementById('settings-frmUrl').text;
            }else{
                alert('Error: Please enter an embed page URL first.');
                return;
            }
        }
        navigator.clipboard.writeText(copyText);
        element.removeAttribute('data-icon');
        if(window.screen.width >= 575){
            const childElement = element.querySelector(".hide-on-mobile");
            childElement.textContent = ' Copied';
        } else{
            const childElement = element.querySelector(".hide-on-desktop"); 
            childElement.classList.add("hidden");
        }
        element.classList.add('success');
        element.setAttribute('data-icon', 'check');
        setTimeout(function(){
            wtbx.setting.resetCopyButtons();
        }, 1500);
    },

    resetCopyButtons: function() {
        var cp = document.querySelectorAll('.copyLink');
        cp.forEach(function(element) {
            if(window.screen.width < 575){
                const childElement = element.querySelector(".hide-on-desktop"); 
                childElement.classList.remove("hidden");
            } else{
                const childElement = element.querySelector(".hide-on-mobile");
                childElement.textContent = 'Copy';
            }
            element.classList.remove('success');
            element.removeAttribute('data-icon');
        });
    },

    sanitizeCommunityURLInput: function() {
        var cmurl = document.getElementById('settings-community-url');
        if(cmurl){
            cmurl.addEventListener('keyup', (event) => {
                cmurl.value = cmurl.value.replace(/[^a-zA-Z0-9\-_]/g, '');
                document.getElementById('settings-frmUrl').text = baseUrl+cmurl.value
            });
        }
    },

    toggleCommunityUrl: function() {
        var embedCheckbox = document.getElementById("settings-forumEmbedded");
        if (embedCheckbox) {
            var subDomainMsg = document.getElementById("settings-alerts");
            var forumAddressElement = document.getElementById("settings-forumAddress");
            var communityWebAddressElement = document.getElementById("settings-communityWebAddress");
            embedCheckbox.addEventListener("change", function() {
                wtbx.setting.resetCopyButtons();
                if(embedCheckbox.checked) {
                    if(subDomainMsg) subDomainMsg.classList.remove('hidden');
                    if(forumAddressElement) forumAddressElement.classList.add("hidden");
                    if(communityWebAddressElement) communityWebAddressElement.classList.remove("hidden");
                }else{
                    if(subDomainMsg) subDomainMsg.classList.add('hidden');
                    if(forumAddressElement) forumAddressElement.classList.remove("hidden");
                    if(communityWebAddressElement) communityWebAddressElement.classList.add("hidden");
                }
                wtbx.setting.submitSettingPageForm();
            });
        }
    }, 
    submitSettingPageForm: function() {
        const settingForm = document.getElementById("main-form");
        if (settingForm) {
            const submitButton = settingForm.querySelector('[type="submit"]');
            if (submitButton) {
                submitButton.click();
            }
        }
    },
    bindCopyTextClick: function() {
        var copyLinkText = document.getElementById("settings-copyLinkText");
        var embedCheckbox = document.getElementById("settings-forumEmbedded");        
        if(copyLinkText) {
            copyLinkText.addEventListener('click', function() {
                var copyLinkButton = document.querySelectorAll('.copyLink');
                if (embedCheckbox && embedCheckbox.checked) {
                    copyLinkButton[0].click();
                } else{
                    copyLinkButton[1].click();
                }
            });
        }
    }
};
wtbx.setting.toggleCommunityUrl();
wtbx.setting.sanitizeCommunityURLInput();
wtbx.setting.bindCopyTextClick();