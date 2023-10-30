

$(document).ready(

    function () {
    
        if ($("#auth_custom_location").length > 0) {
            $("#auth_custom_location").append(buttonsCode);
        } else {
            $formObj = $("input[name='username']").closest("form");
            if ($formObj.length > 0) {
                $($formObj).each(function (i, formItem) {
                    $username = $(formItem).find("input[name='username']").val();
                    $password = $(formItem).find("input[name='password']").val();
                    if ($username != "guest" || $password != "guest") {
                        $(formItem).append(buttonsCode);
                    }
                });
            }
        }
        
        var telegramWidget = document.createElement("script");
        telegramWidget.async = true;
        telegramWidget.src = "https://telegram.org/js/telegram-widget.js?7";
        telegramWidget.dataset.telegramLogin = botusername;;
        telegramWidget.dataset.size = "large";
        telegramWidget.dataset.radius = "5";
        telegramWidget.dataset.authUrl = M.cfg.wwwroot+"/auth/telegram/telegram_auth.php";
        
        // Append the script to the document
        var div=document.getElementById("telegram-login-container") ;
        div.appendChild(telegramWidget);
    }
    
    
)