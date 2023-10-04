wtbx.setting = {

    checkAll: function(e){
        document.getElementById('settings-allOptions').style.display = "none";
        document.getElementById('settings-all_users').checked = true;
        var checkboxes = document.getElementsByName('settings[user_roles][]');
        if (e.checked) {
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = true;
            }
        }else{
            document.getElementById('settings-no_users').checked = true;                
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = false;
            }
        }
    },
    
    unCheckAll: function(e){    
        document.getElementById('settings-allOptions').style.display = "none";
        var checkboxes = document.getElementsByName('settings[user_roles][]');
        if (e.checked) {
            for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = false;
            }
        }
    },
    
    showOptions: function(){    
        var userRoles = document.getElementById('settings-hiddenUserRoles').value;
        var arrayUserRoles = userRoles.split(",");
        var checkboxes = document.getElementsByName('settings[user_roles][]');
        for (var i = 0; i < checkboxes.length; i++) {
                if(arrayUserRoles.includes(checkboxes[i].value) != ''){
                    checkboxes[i].checked = true;
                }else{
                    checkboxes[i].checked = false;
                }
            }
        document.getElementById('settings-allOptions').style.display = "block";    
    },

    copyUrl: function(me, textToCopy='') {
        if(textToCopy){
            navigator.clipboard.writeText(textToCopy);
            me.text = '';
            me.removeAttribute('data-icon');
            setTimeout(function(){
                me.text = ' Copied'
                me.classList.add('success');
                me.setAttribute('data-icon', 'check');
            }, 200);
        }else{
            var page = document.getElementById('settings-communityUrl').value;
            if(page !== ''){
                var copyText = document.getElementById('settings-frmUrl').text;
                navigator.clipboard.writeText(copyText);            
                me.text = '';
                me.removeAttribute('data-icon');
                setTimeout(function(){
                    me.text = ' Copied'
                    me.classList.add('success');
                    me.setAttribute('data-icon', 'check');
                }, 200);
            }else{
                alert('Error: Please enter an embed page URL first.');
            }    
        }
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
    }

};