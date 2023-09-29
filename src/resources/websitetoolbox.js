var checkAll = function(e){
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
}

var unCheckAll = function(e){    
    document.getElementById('settings-allOptions').style.display = "none";
    var checkboxes = document.getElementsByName('settings[user_roles][]');
    if (e.checked) {
        for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = false;
        }
    }
}

var showOptions = function(){    
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
}