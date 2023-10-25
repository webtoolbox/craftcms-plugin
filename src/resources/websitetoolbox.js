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
        element.text = '';
        element.removeAttribute('data-icon');
        setTimeout(function(){
            element.text = ' Copied'
            element.classList.add('success');
            element.setAttribute('data-icon', 'check');
        }, 200);
    },

    resetCopyButtons: function() {
        var cp = document.getElementsByClassName('copyLink');
        cp[0].innerHTML = cp[1].innerHTML = 'Copy URL';
        cp[0].classList.remove('success'); 
        cp[1].classList.remove('success');
        cp[0].removeAttribute('data-icon'); 
        cp[1].removeAttribute('data-icon'); 
    },

    visibilityOfEmbeddedOption: function() {
        var checkbox = document.getElementById('settings-forumEmbedded');
        if (checkbox) {
            if (!checkbox.checked) {
                document.getElementById('settings-cmUrl').style.display = 'none';
            } else {
                document.getElementById('settings-cmInstruction').style.display = 'none';
            }
        }
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
        var checkbox = document.getElementById('settings-forumEmbedded');
        if (checkbox) {
            if (!checkbox.checked) {
                document.getElementById('settings-cmInstruction').style.display = 'table-row';
            } else {
                document.getElementById('settings-cmUrl').style.display = 'table-row';
                
            }
            checkbox.addEventListener('change', (event) => {
                var chk = event.target;
                wtbx.setting.resetCopyButtons();
                if (chk.checked) {
                    document.getElementById('settings-cmInstruction').style.display = 'none';
                    document.getElementById('settings-cmUrl').style.display = 'table-row'; 
                } else {
                    document.getElementById('settings-cmUrl').style.display = 'none';
                    document.getElementById('settings-cmInstruction').style.display = 'table-row';
                }
            });
        }
    }
};

wtbx.setting.toggleCommunityUrl();
wtbx.setting.sanitizeCommunityURLInput();