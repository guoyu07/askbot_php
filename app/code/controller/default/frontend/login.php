<?phpif (isset($_POST["action"]) && $_POST["action"] == "login_local") {		$db = new SQL(0);	if (MyUser::isloggedin()) {		$row = $db->cmdrow(0, 'SELECT * FROM user_login WHERE user = {0} AND provider = "local" LIMIT 0,1', array(MyUser::id()));		if ($row["pwd"]."" != "" AND $row["pwd"] != md5($_POST["password1"])) PageEngine::AddErrorMessage("login", "Falsches bisheriges Passwort");		elseif ($_POST["password2"] != $_POST["password3"]) PageEngine::AddErrorMessage("login", "Passwort und Wiederholung sind unterschiedlich");		else {			MyUser::changePassword($_POST["password2"]); 			PageEngine::AddSuccessMessage("login", "Passwort geändert");		}	} else {				$row = $db->cmdrow(0, 'SELECT T1.id, T2.pwd FROM user_list as T1 LEFT JOIN user_login as T2 ON T1.id=T2.user WHERE (LOWER(T1.username) = "{0}" OR email_standard = "{0}") AND provider="local" LIMIT 0,1', array(strtolower($_POST["username"])));		if (!isset($row["id"])) PageEngine::AddErrorMessage("login", "Ungültiger Benutzername oder Passwort");		elseif ($row["pwd"] != md5($_POST["password"])) PageEngine::AddErrorMessage("login", "Ungültiger Benutzername oder Passwort (2)");		else {			MyUser::loginload($row["id"]);			header("Location: ".get_path("/?t=".time()));			exit(1);		}	}}	$fb = new LoginFacebook(array(  		"appId"  => SiteConfig::val("facebook/appid"),  		"secret" => SiteConfig::val("facebook/secret"),  		"cookie" => true  		));if (isset($_GET["action"]) && $_GET["action"] == "login_facebook") {	if ($fb->getUser() == 0) { @header("Location: ".$fb->getLoginUrl()); exit(1); }	$user = $fb->api("/me");	$db = new SQL(0);	if (MyUser::isloggedin()) {		$w = array();		$w["username"] = $user["id"];		$w["provider"] = "facebook";		$w["user"] = MyUser::id();		$db->CreateUpdate(0, 'user_login', $w);		PageEngine::AddSuccessMessage("openid", "Zugriffsart via. Facebook hinzugefügt");	} else {	$row = $db->cmdrow(0, 'SELECT * FROM user_login WHERE username="{0}" AND provider="facebook" LIMIT 0,1', array($fb->getUser()));	if (!isset($row["username"])) {		$db->cmd(0, 'INSERT IGNORE INTO user_list ');		$w = array();		$w["username"] = $user["name"]."#".rand(0,99999);		$w["email_standard"] = $user["username"]."@facebook.com";		$w["dt_registered"] = time();		$db->Create(0, 'user_list', $w);		$userid = $db->LastInsertKey();		$w2["username"] = $user["id"];		$w2["provider"] = "facebook";		$w2["user"] = $userid;		$db->Create(0, 'user_login', $w2);		$row = $db->cmdrow(0, 'SELECT * FROM user_login WHERE username="{0}" AND provider="facebook" LIMIT 0,1', array($fb->getUser()));	}	MyUser::loginload($row["user"]);	header("Location: ".get_path("/?t=".time()));	exit(1);	}}if (isset($_GET["action"]) && $_GET["action"] == "login_yahoo") {	DoOpenIDLogin("https://me.yahoo.com/");}if (isset($_GET["action"]) && $_GET["action"] == "login_aol") {	DoOpenIDLogin("https://www.aol.com");}if (isset($_GET["action"]) && $_GET["action"] == "login_symantec") {	DoOpenIDLogin("https://pip.verisignlabs.com/");}if (isset($_GET["action"]) && $_GET["action"] == "login_google") {	$p = 'https://www.google.com/accounts/o8/ud?openid.ns=http://specs.openid.net/auth/2.0&openid.ns.ax=http://openid.net/srv/ax/1.0&openid.claimed_id=http://specs.openid.net/auth/2.0/identifier_select&openid.identity=http://specs.openid.net/auth/2.0/identifier_select&openid.return_to='.$_ENV["baseurl"].'account/signin?action=login_google_response&openid.realm=http://'.$_SERVER["HTTP_HOST"].'/&openid.assoc_handle=ABSmpf6DNMw&openid.mode=checkid_setup&openid.ax.required=country,email,firstname,language,lastname&openid.ax.mode=fetch_request&openid.ax.type.email=http://axschema.org/contact/email&openid.ax.type.country=http://axschema.org/contact/country/home&openid.ax.type.language=http://axschema.org/pref/language&openid.ax.type.firstname=http://axschema.org/namePerson/first&openid.ax.type.lastname=http://axschema.org/namePerson/last';	header('Location: '.str_replace(array(chr(13),chr(10)),"",$p));	exit(1);}if (isset($_GET["action"]) && $_GET["action"] == "login_google_response") {	$username = md5($_GET["openid_identity"]);	$db = new SQL(0);	$row = $db->cmdrow(0, 'SELECT * FROM user_login WHERE username="{0}" AND provider="google" LIMIT 0,1', array($username));	if (!isset($row["username"])) {		$db->cmd(0, 'INSERT IGNORE INTO user_list ');		$w = array();		$w["username"] = $_GET["openid_ext1_value_firstname"]."".$_GET["openid_ext1_value_lastname"]."#".rand(0,99999);		$w["email_standard"] = $_GET["openid_ext1_value_email"];		$w["dt_registered"] = time();		$db->Create(0, 'user_list', $w);		$userid = $db->LastInsertKey();		$w2["username"] = $username;		$w2["provider"] = "google";		$w2["user"] = $userid;		$db->Create(0, 'user_login', $w2);		$row = $db->cmdrow(0, 'SELECT * FROM user_login WHERE username="{0}" AND provider="google" LIMIT 0,1', array($username));	}	MyUser::loginload($row["user"]);	header("Location: ".get_path("/?t=".time()));	exit(1);	//http://127.0.0.1:8082/askbot/account/signin?action=login_google_response&openid.ns=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0&openid.mode=id_res&openid.op_endpoint=https%3A%2F%2Fwww.google.com%2Faccounts%2Fo8%2Fud&openid.response_nonce=2012-10-29T22%3A16%3A45ZxLgZyEdIOppIAw&openid.return_to=http%3A%2F%2F127.0.0.1%3A8082%2Faskbot%2Faccount%2Fsignin%3Faction%3Dlogin_google_response&openid.invalidate_handle=ABSmpf6DNMw&openid.assoc_handle=AMlYA9VO0cDrs_DCb7dwBpWED9qHF0QtHhjdwcLQRwhuf_oHmGjGBQ_d&openid.signed=op_endpoint%2Cclaimed_id%2Cidentity%2Creturn_to%2Cresponse_nonce%2Cassoc_handle%2Cns.ext1%2Cext1.mode%2Cext1.type.firstname%2Cext1.value.firstname%2Cext1.type.email%2Cext1.value.email%2Cext1.type.language%2Cext1.value.language%2Cext1.type.lastname%2Cext1.value.lastname&openid.sig=3H3PjRiFw6w2proHrClS7ChQwWI%3D&openid.identity=https%3A%2F%2Fwww.google.com%2Faccounts%2Fo8%2Fid%3Fid%3DAItOawlJe3brb90Yfk7l938jJsrK9Rv0i7vj_6Q&openid.claimed_id=https%3A%2F%2Fwww.google.com%2Faccounts%2Fo8%2Fid%3Fid%3DAItOawlJe3brb90Yfk7l938jJsrK9Rv0i7vj_6Q&openid.ns.ext1=http%3A%2F%2Fopenid.net%2Fsrv%2Fax%2F1.0&openid.ext1.mode=fetch_response&openid.ext1.type.firstname=http%3A%2F%2Faxschema.org%2FnamePerson%2Ffirst&openid.ext1.value.firstname=Andreas&openid.ext1.type.email=http%3A%2F%2Faxschema.org%2Fcontact%2Femail&openid.ext1.value.email=investmentinformatiker%40googlemail.com&openid.ext1.type.language=http%3A%2F%2Faxschema.org%2Fpref%2Flanguage&openid.ext1.value.language=en&openid.ext1.type.lastname=http%3A%2F%2Faxschema.org%2FnamePerson%2Flast&openid.ext1.value.lastname=Kasper}function DoOpenIDLogin($url) {	$openid = new LightOpenID($_SERVER["HTTP_HOST"]);	if (!$openid->mode) {		$openid->identity = $url;		$openid->optional = array("namePerson/friendly","namePerson","namePerson/first","contact/email","birthDate","person/gender","contact/postalCode/home","contact/country/home","pref/language");		header('Location: ' . $openid->authUrl()); exit(1);		}	if ($openid->validate()) {		$id = $openid->data["openid_identity"];		$email = $openid->data["openid_ax_value_email"];		$nickname = $openid->data["openid_ax_value_nickname"];		$language = $openid->data["openid_ax_value_language"];		$gender = $openid->data["openid_ax_value_gender"];		//print_r($openid); exit(1);		if (MyUser::isloggedin()) OpenIDAddLogin($id, array("email" => $email, "nickname" => $nickname, "language" => $language, "gender" => $gender));		else { OpenIDRegisterLogin($id, array("email" => $email, "nickname" => $nickname, "language" => $language, "gender" => $gender)); exit(1); }	}	//print_r($_GET);}function OpenIDAddLogin($openIdentifier, $data) {	$userlist = $openIdentifier;	$db = new SQL(0);	$w = array();	$w["username"] = $userlist;	$w["provider"] = "openid";	$w["user"] = MyUser::id();	$db->CreateUpdate(0, 'user_login', $w);	PageEngine::AddSuccessMessage("openid", "Zugriffsart hinzugefügt");}function OpenIDRegisterLogin($openIdentifier, $data) {	$userlist = $openIdentifier;	$db = new SQL(0);	$row = $db->cmdrow(0, 'SELECT * FROM user_login WHERE username="{0}" AND provider="openid" LIMIT 0,1', array($userlist));	if (!isset($row["username"])) {		$db->cmd(0, 'INSERT IGNORE INTO user_list ');		$w = array();		if (isset($data["nickname"])) $w["username"] = $data["nickname"]."#".rand(0,99999); else $w["username"] = "User#".rand(0,99999);		$w["email_standard"] = $data["email"];		if (isset($data["language"])) $w["language"] = str_replace("-", "_", $data["language"]);		$w["dt_registered"] = time();		$db->Create(0, 'user_list', $w);		$userid = $db->LastInsertKey();		$w2["username"] = $userlist;		$w2["provider"] = "openid";		$w2["user"] = $userid;		$db->Create(0, 'user_login', $w2);		$row = $db->cmdrow(0, 'SELECT * FROM user_login WHERE username="{0}" AND provider="openid" LIMIT 0,1', array($userlist));	}	MyUser::loginload($row["user"]);	header("Location: ".get_path("/?t=".time()));	exit(1);}