<?php
	function isLoggedIn() {	
	    $blnLoggedIn = false;
	    if (!empty($_SESSION['userid'])) {			
	        if ($_SESSION['fingerprint'] == getSessionFingerprint()) {	
	            $blnLoggedIn = true;
	        }	 else {		
	            doLogout();
	        }
	    }		
	    return $blnLoggedIn;
	}
	
	function loginByCookie() {
	    $blnLoggedIn = false;   
	    if ($intUserId = getUserIdCookie()) {
	        if ($arrUser = loadUserById($intUserId)) {
	            $blnLoggedIn = doLogin($arrUser, true);
	        }     
	        if (!$blnLoggedIn) {
	            doLogout();
	        }
	    }    
	    return $blnLoggedIn;
	}
	
	function doLogin($arrUser, $blnFromCookie) {
	    session_regenerate_id(true);
	    $_SESSION['fingerprint'] = getSessionFingerprint();
	    $_SESSION['userid'] = $arrUser['userid'];
	    
	    return $blnFromCookie || setUserIdCookie($arrUser['userid']);
	}
	
	function loginByForm($strUsername, $strPassword) {
	    $blnLoggedIn = false;
	    if ($arrUser = loadUserByUsername($strUsername)) {
	        if (Password::validate($arrUser['password'], $strPassword)) {
	            $blnLoggedIn = doLogin($arrUser, false);
	        }
	    }
	    return $blnLoggedIn;
	}
	
	function getSessionFingerprint() {
	    return md5(
	        getConfigVar('SessionFingerprintSalt') .		
	        $_SERVER['HTTP_USER_AGENT']	.		
	        session_id()		
	    );
	}
	
	function doLogout() {
	    setcookie(session_name(), '', time() - 3600, '/');
	    setcookie('login', '', time() - 3600, '/');
	
	    $_SESSION = array();
	    session_destroy();
	}
	
	function getUserIdCookie() {
	    $intValidId = null;  
	    if (!empty($_COOKIE['login'])) {
	        list($intUserId, $strPublicKey) = explode(':', $_COOKIE['login']);
	        if ((int) $intUserId && $strPublicKey) {
	            if ($arrUserLogin = loadUserLoginByPublicKey($intUserId, $strPublicKey)) {
	                if ($arrUserLogin['privatekey'] == generatePrivateKey($intUserId)) {
	                    $intValidId = $intUserId;
	                    updateUserLoginAccessedDate($arrUserLogin['userloginid']);
	                } else{
	                    deleteUserLogin($arrUserLogin['userloginid']);
	                }
	            }
	        }
	        if (!$intValidId) {
	             doLogout();
	        }
	    }    
	    return $intValidId;
	}
	
	function setUserIdCookie($intUserId) {
	    $strPrivateKey = generatePrivateKey($intUserId);
	    if ($arrUserLogin = loadUserLoginByPrivateKey($intUserId, $strPrivateKey)) {
	        $strPublicKey = $arrUserLogin['publickey'];
	        updateUserLoginAccessedDate($arrUserLogin['userloginid']);
	    } else {		
	        $strPublicKey = md5(rand() . microtime());
	        if (!saveNewUserLogin($intUserId, $strPrivateKey, $strPublicKey)) {
			          $strPublicKey = null;
	        }
	    }
	    
	    if ($strPublicKey) {
	        $strCookieValue = $intUserId . ':' . $strPublicKey;
	        setcookie('login', $strCookieValue, time() + (86400 * 365), '/');
	        return true;
	    }
	}
	
	function generatePrivateKey($intUserId) {
	    if (@ini_get('browscap')) {
	        $objBrowser = get_browser();
	        $strIdentity = $objBrowser->platform . $objBrowser->parent;
	    } else {
	        $strIdentity = $_SERVER['HTTP_USER_AGENT'];
	    }
	    return md5($intUserId . $strIdentity . getConfigVar('LoginKeySalt'));
	}
	
	
	
	
	function flushExpiredUserLogins($intUserId) {
	    if ($intMaxConcurrentLogins = getConfigVar('MaxConcurrentLogins')) {
	        if ($arrUserLogins = loadAllUserLoginsByAccessedDesc($intUserId)) {
	            if (count($arrUserLogins) > $intMaxConcurrentLogins) {
	                deleteUserLoginsById(array_slice($arrUserLogins, $intMaxConcurrentLogins));
	            }
	        }
	    }
	}
	
	
	class Password {
	
	    const HASH_ALGORITHM = 'SHA-1';
	    const SALT_LENGTH = 6;
	    const SALT_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	    const FIELD_SEPARATOR = ':';    
	    
	    static public function validate($strEncryptedPassword, $strRawPassword) {
	        list($strAlgorithm, $strSalt, $strHashedPassword) = explode(self::FIELD_SEPARATOR, $strEncryptedPassword);
	        return $strHashedPassword == self::hash($strAlgorithm, $strSalt, $strRawPassword);
	    }
	    
	    static public function encrypt($strRawPassword) {
	        $strSalt = substr(str_shuffle(self::SALT_CHARS), 0, self::SALT_LENGTH);
	        return self::HASH_ALGORITHM . self::FIELD_SEPARATOR . 
	               $strSalt . self::FIELD_SEPARATOR . 
	               self::hash(self::HASH_ALGORITHM, $strSalt, $strRawPassword)
	        ;
	    }
	    
	    static protected function hash($strAlgorithm, $strSalt, $strRawPassword) {
	        $strAlgorithm = strtolower(preg_replace('/\W/', '', $strAlgorithm));
	        return hash($strAlgorithm, $strSalt . $strRawPassword);
	    }
	}
?>

CREATE TABLE `users` (
  `userid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(80) NOT NULL,
  `created` datetime NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`userid`),
  KEY `username` (`username`)
);

CREATE TABLE `user_login` (
  `userloginid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `publickey` char(32) NOT NULL,
  `privatekey` char(32) NOT NULL,
  `created` datetime NOT NULL,
  `accessed` datetime NOT NULL,
  PRIMARY KEY (`userloginid`),
  UNIQUE KEY `user` (`userid`,`privatekey`)
);