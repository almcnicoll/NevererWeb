/* UPDATE */
/* VERSION 1 */
INSERT INTO `authmethods` (`methodName`,`handler`,`image`,`created`,`modified`)
VALUES ('google','login-google.php','img/logins/google.png',NOW(),NOW())
;
/* UPDATE */
/* VERSION 2 */
ALTER TABLE `authmethods` MODIFY COLUMN `image` varchar(4000) NULL;
UPDATE `authmethods` SET `handler`='', `image`='<div style="width:400px; margin:auto;"><div id="g_id_onload" data-client_id="113954362296-1t4ieb2ghbcoqejmphksgqq7u7nhcp83.apps.googleusercontent.com" data-context="use" data-ux_mode="popup" data-login_uri="/NevererWeb/login-google.php" data-auto_select="true" data-itp_support="true"></div><div class="g_id_signin" data-type="standard" data-shape="pill" data-theme="filled_blue" data-text="continue_with" data-size="large" data-logo_alignment="left"></div></div>' WHERE `methodName`='google';