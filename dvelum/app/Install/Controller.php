<?php
class Install_Controller {
    /**
     * Document root
     * @var string
     */
    protected $docRoot;
    /**
     * Template
     * @var Template
     */
    protected $template;

    /**
     * @var Lang $localization
     */
    protected $localization;

    /**
     * @var Config_Abstract $applicationConfig
     */
    protected $appConfig;

    protected $lang;
    protected $phpExt;

    protected $session;
    protected $wwwRoot;

    /**
     * @var Autoloader
     */
    protected $autoloader;

    public function __construct()
    {
        $this->session = Store::factory(Store::Session , 'install');
        $this->appConfig = Config::storage()->get('main.php' , false , false);
        $this->wwwPath = DVELUM_WWW_PATH;
        $this->docRoot = DVELUM_ROOT;
        $this->wwwRoot = '/';

        $uri = $_SERVER['REQUEST_URI'];
        $parts = explode('/', $uri);
        for($i=1;$i<sizeof($parts);$i++)
        {
            if($parts[$i]==='install'){
                break;
            }
            $this->wwwRoot.=$parts[$i].'/';
        }
        /*
         * Set localization storage options
         */
        Lang::setStorageOptions(
            Config::storage()->get('lang_storage.php')->__toArray()
        );
    }

    public function setAutoloader(Autoloader $autoloader)
    {
        $this->autoloader = $autoloader;
    }

    public function run()
    {
        $action = Request::post('action', 'string', false);
        $lang = $this->session->get('lang');

        if(!empty($lang))
            $this->lang = $lang;
        else
            $this->lang = 'en';

        $this->localization = Lang::storage()->get($this->lang  . '/install.php');

        if($action !== false && method_exists($this, $action . 'Action'))
            $this->_action = strtolower($action) . 'Action';
        else
            $this->_action = 'indexAction';

        $this->{$this->_action}();
    }

    public function setlangAction()
    {
        $lang = Request::post('lang', 'string', false);

        if(empty($lang))
            Response::jsonError();

        $this->session->set('lang', $lang);
        Response::jsonSuccess();
    }

    public function indexAction()
    {
        $this->template = new Template();
        $this->template->url = './index.php';
        $this->template->lang = $this->lang;
        $this->template->dictionary = $this->localization;
        $this->template->wwwRoot = $this->wwwRoot;

        if($this->lang == 'ru')
            $this->template->license = file_get_contents('./data/gpl-3.0_ru.txt');
        else
            $this->template->license = file_get_contents('./data/gpl-3.0_en.txt');

        echo $this->template->render('./application/templates/install/install.php');
    }

    protected function _checkWritable($path, $required, $msg){
        $data = array();
        $data['title'] = $this->localization->get('WRITE_PERMISSIONS') . ' ' . $path;
       // @chmod($this->docRoot . $path, 0775);
        if(is_writable($path)){
            $data['success'] = true;
        }else{
            $data['success'] = !$required;
            $data['error'] = $msg;
        }
        return $data;
    }

    protected function _checkExtention($extention, $required, $msg = false){
        $data = array();
        $data['title'] = $this->localization->get('LIBRARY_CHECK') . ' ' . $extention;
        if (in_array($extention, $this->phpExt, true)) {
            $data['success'] = true;
        } else {
            $data['success'] = !$required;
            $data['error'] = $msg ? $msg : $this->localization->get('LIBRARY_NOT_EXISTS');
        }
        return $data;
    }

    public function firstcheckAction() {
        $data = array();
        $this->phpExt = get_loaded_extensions();

        /**
         * Check for php version
         */
        $data['items'][0]['title'] = $this->localization->get('PHP_V') . ' >= 5.5.0';
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            $data['items'][0]['success'] = false;
            $data['items'][0]['error'] = $this->localization->get('UR_PHP_V') . ' ' . PHP_VERSION;
        } else
            $data['items'][0]['success'] = true;

        $extentions = array(
            array(
                'name'=>'memcache',
                'accessType'=>'allowed',
                'msg'=>$this->localization->get('PERFORMANCE_WARNING')
            ),
            array(
                'name'=>'mysqli',
                'accessType'=>'required',
                'msg'=>false
            ),
            array(
                'name'=>'gd',
                'accessType'=>'required',
                'msg'=>false
            ),
            array(
                'name'=>'mbstring',
                'accessType'=>'required',
                'msg'=>false
            ),
            array(
                'name'=>'curl',
                'accessType'=>'allowed',
                'msg'=>$this->localization->get('DEPLOY_WARNING')
            ),
            array(
                'name'=>'zip',
                'accessType'=>'allowed',
                'msg'=>$this->localization->get('WARNING')
            ),
            array(
                'name' => 'mcrypt',
                'accessType'=>'allowed',
                'msg'=>$this->localization->get('WARNING')
            ),
            array(
                'name'=>'json',
                'accessType'=>'required',
                'msg'=>false
            )
        );

        $writablePaths = array(
            array(
                'path'=>'application/configs/local',
                'accessType'=>'required'
            ),
            array(
                'path'=>'application/locales/local',
                'accessType'=>'required'
            ),
            array(
                'path'=>'temp',
                'accessType'=>'required'
            ),
            array(
                'path'=>'data/logs',
                'accessType'=>'required'
            ),
            array(
                'path'=>$this->wwwPath . 'js/lang',
                'accessType'=>'required'
            ),
            array(
                'path'=>$this->wwwPath . 'js/cache',
                'accessType'=>'required'
            ),
            array(
                'path'=>$this->wwwPath . 'js/syscache',
                'accessType'=>'required'
            ),
            array(
                'path'=>$this->wwwPath . 'media',
                'accessType'=>'required'
            ),

        );

        foreach ($extentions as $v){
            switch ($v['accessType']){
                case 'required':
                    $data['items'][] = $this->_checkExtention($v['name'], true, $v['msg']);
                    break;
                case 'allowed':
                    $data['items'][] = $this->_checkExtention($v['name'], false, $v['msg']);
                    break;
            }
        }

        foreach ($writablePaths as $v){
            switch ($v['accessType']){
                case 'required':
                    $data['items'][] = $this->_checkWritable($v['path'], true, $this->localization->get('RECORDING_IS_PROHIBITED'));
                    break;
                case 'allowed':
                    $data['items'][] = $this->_checkWritable($v['path'], false, $this->localization->get('RECORDING_IS_PROHIBITED_OK'));
                    break;
            }
        }

        $data['info'] = $this->localization->get('WARNING_ABOUT_RIGHTS');

        Response::jsonSuccess($data);
    }
    public function dbcheckAction() {
        $host = Request::post('host', 'str', '');
        $port = Request::post('port', 'int', 0);
        $prefix = Request::post('prefix', 'str', '');

        $installDocs = Request::post('install_docs' , 'boolean' , false);
        $this->session->set('install_docs' , $installDocs);

        $params = array(
            'host'           => $host,
            'username'       => Request::post('username', 'str', false),
            'password'       => Request::post('password', 'str', false),
            'dbname'         => Request::post('dbname', 'str', false),
            'adapter'  => 'Mysqli',
            'adapterNamespace' => 'Db_Adapter'
        );

        if ($port != 0)
            $params['port'] = $port;

        $flag = false;
        if ($params['host'] && $params['username'] && $params['dbname'])
            try {
                $zendDb = Zend_Db::factory('Mysqli', $params);
                $zendDb->getServerVersion();
                $data['success'] = true;
                $data['msg'] = $this->localization->get('SUCCESS_DB_CHECK');

                $flag = true;

            } catch (Exception $e) {
                $data['success'] = false;
                $data['msg'] = $this->localization->get('FAILURE_DB_CHECK');
            }
        else {
            $data['success'] = false;
            $data['msg'] = $this->localization->get('REQUIRED_DB_SETTINGS');
        }

        if ($flag){
            try {

                $configs = array(
                     'db/prod/default.php',
                     'db/prod/error.php',

                     'db/dev/default.php',
                     'db/dev/error.php',
                );

                $params['prefix'] = $prefix;
                $params['charset'] = 'UTF8';

                foreach($configs as $item)
                {
                    $cfg = Config::storage()->get($item , false, false);
                    $cfg->setData($params);

                    if (!$cfg->save())
                        throw new Exception();
                }

            } catch (Exception $e) {
                $data['success'] = false;
                $data['msg'] = $this->localization->get('CONNECTION_SAVE_FAIL');
            }
        }
        Response::jsonSuccess($data);
    }
    /**
     * Create Database tables
     */
    public function createtablesAction()
    {
        $mainConfig = Config::storage()->get('main.php', false ,true);

        $app = new Application($mainConfig);
        $app->setAutoloader($this->autoloader);
        $app->init();

        $dbObjectManager = new Db_Object_Manager();
        $objects = $dbObjectManager->getRegisteredObjects();

        foreach ($objects as $name)
        {
            $dbObjectBuilder = new Db_Object_Builder($name);
            if(!$dbObjectBuilder->build())
                $buildErrors[] = $name;
        }

        // install documentation
        if($this->session->get('install_docs')){
            $this->installDocs();
        }

        if(!empty($buildErrors))
            Response::jsonError($this->localization->get('BUILD_ERR') . ' ' . implode(', ', $buildErrors));
        else
            Response::jsonSuccess('', array('msg'=>$this->localization->get('DB_DONE')));
    }

    /**
     * Install documentation
     * @throws Exception
     */
    public function installDocs()
    {
        $installCfg = Config::storage()->get('install.php');
        $dataPath = $installCfg->get('dumpdir');
        $objectList = $installCfg->get('objects');
        $chunkSize = $installCfg->get('chunk_size');

        foreach($objectList as $object=>$fields)
        {
            $filePath = $dataPath.$object.'.csv';

            if(!file_exists($filePath)){
                Response::jsonError($this->localization->get('INSTALL_DOCS_ERROR') .' '. $this->localization->get('IMPORT_ERR').' '.$filePath);
            }

            $model =  Model::factory($object);
            $db = $model->getDbConnection();
            $csvHandler = fopen($filePath , 'r');
            $rows = [];
            while(($row = fgetcsv($csvHandler , 0,';','"'))!==false)
            {
                foreach($row as $k=>&$v){
                    if($v==='NULL'){
                        $v = null;
                    }
                }unset($v);
                if(count($rows) < $chunkSize){
                    $rows[] = array_combine($fields , $row);
                }else{
                    if(!$model->multiInsert($rows , $chunkSize)){
                        Response::jsonError($this->localization->get('INSTALL_DOCS_ERROR'));
                    }
                    $rows = [];
                }

            }
            if(!empty($rows)){
                if(!$model->multiInsert($rows , $chunkSize)){
                    Response::jsonError($this->localization->get('INSTALL_DOCS_ERROR'));
                }
            }
            fclose($csvHandler);
        }
    }

    public function setuserpassAction()
    {
        $pass = Request::post('pass', 'str', '');
        $passConfirm = Request::post('pass_confirm', 'str', '');
        $lang = Request::post('lang', 'string', 'en');
        $timezone = Request::post('timezone', 'string', '');
        $email = Request::post('adm_email', 'string', '');
        $adminpath = strtolower(Request::post('adminpath', 'string', ''));
        $user = Request::post('user',  'str', '');

        $errors = array();

        if(!strlen($user))
            $errors[] = $this->localization->get('INVALID_USERNAME');

        if(empty($pass) || empty($passConfirm) || $pass != $passConfirm)
            $errors[] = $this->localization->get('PASS_MISMATCH');

        $timezones = timezone_identifiers_list();
        if(empty($timezone) || !in_array($timezone, $timezones, true))
            $errors[] = $this->localization->get('TIMEZOME_REQUIRED');

        if(!Validator_Email::validate($email))
            $errors[] = $this->localization->get('INVALID_EMAIL');

        if(!Validator_Alphanum::validate($adminpath)  || is_dir('./dvelum/app/Backend/'.ucfirst($adminpath)))
            $errors[] = $this->localization->get('INVALID_ADMINPATH');

        if(!empty($errors))
            Response::jsonError(implode(', ', $errors));

        $mainConfig = array(
            'salt'=> Utils::getRandomString(4) . '_' . Utils::getRandomString(4),
            'development' => 1,
            'timezone' => $timezone,
            'adminPath'=> $adminpath,
            'language' =>$lang,
            'wwwroot' => $this->wwwRoot
        );

        $mainCfg = Config::storage()->get('main.php' , false , false);
        $writePath = $mainCfg->getWritePath();

        if(!is_dir(dirname($writePath)) && !@mkdir($writePath , 0655, true)){
            Response::jsonError($this->localization->get('CANT_WRITE_FS').' '.dirname($writePath));
        }

        if(!Utils::exportArray($writePath , $mainConfig))
            Response::jsonError($this->localization->get('CANT_WRITE_FS').' '.$writePath);


        if(extension_loaded('mcrypt')){
            $key = base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB),MCRYPT_DEV_RANDOM));
        }else{
            $key = md5(uniqid(md5(time())));
        }

        $encConfig = array(
            'key' =>  $key,
            'iv_field' => 'enc_key'
        );

        $encFields = Config::storage()->get($mainCfg->get('object_configs').'/enc/config.php', false , false);
        $encFields->setData($encConfig);

        if(!$encFields->save())
            Response::jsonError($this->localization->get('CANT_WRITE_FS') . ' '.$encFields->getWritePath());


        $mainConfig = Config::storage()->get('main.php', false ,true);
        Registry::set('main', $mainConfig , 'config');

        /*
         * Starting the application
         */
        $app = new Application($mainConfig);
        $app->setAutoloader($this->autoloader);
        $app->init();

        if(!$this->_prepareRecords($pass, $email, $user))
            Response::jsonError($this->localization->get('CANT_WRITE_TO_DB'));

        Response::jsonSuccess(array('link'=>Registry::get('main' , 'config')->get('adminPath')));
    }

    protected function _prepareRecords($adminPass , $adminEmail, $adminName)
    {
        try
        {
            $toCleanModels = array(
                Model::factory('User'),
                Model::factory('Group'),
                Model::factory('Permissions'),
                Model::factory('Page')
            );

            foreach ($toCleanModels as $model)
                $model->getDbConnection()->delete($model->table());

            // Add group
            $group = new Db_Object('Group');
            $group->setValues(array(
                'title'=>$this->localization->get('ADMINISTRATORS') ,
                'system'=>true
            ));
            $group->save(true, false);
            $groupId = $group->getId();

            // Add user
            $user = new Db_Object('user');

            $user->setValues(array(
                    'name' =>'Admin',
                    'email' => $adminEmail,
                    'login' => $adminName,
                    'pass' => password_hash($adminPass , PASSWORD_DEFAULT),
                    'enabled' => true,
                    'admin' => true,
                    'registration_date' => date('Y-m-d H:i:s'),
                    'confirmation_code' => md5(date('Y-m-d H:i:s')),
                    'group_id' => $groupId,
                    'confirmed' => true,
                    'avatar' => '',
                    'registration_ip' => $_SERVER['REMOTE_ADDR'],
                    'last_ip' => $_SERVER['REMOTE_ADDR'],
                    'confirmation_date' =>date('Y-m-d H:i:s')
                )
            );
            $userId = $user->save(false, false);
            if(!$userId)
                return false;

            // Add permissions
            $permissionsModel = Model::factory('Permissions');
            $modulesManager = new Modules_Manager();
            $modules = $modulesManager->getList();

            foreach ($modules as $name=>$config)
                if(!$permissionsModel->setGroupPermissions($groupId , $name , true, true , true , true))
                    return false;

            $u = User::getInstance();
            $u->setId($userId);
            $u->setAuthorized();

            // Add index Page
            $page = new Db_Object('Page');
            $page->setValues(array(
                'code'=>'index',
                'is_fixed'=>1,
                'html_title'=>'Index',
                'menu_title'=>'Index',
                'page_title'=>'Index',
                'meta_keywords'=>'',
                'meta_description'=>'',
                'parent_id'=>null,
                'text' =>'[Index page content]',
                'func_code'=>'',
                'order_no' => 1,
                'show_blocks'=>true,
                'published'=>true,
                'published_version'=>0,
                'editor_id'=>$userId,
                'date_created'=>date('Y-m-d H:i:s'),
                'date_updated'=>date('Y-m-d H:i:s'),
                'author_id'=>$userId,
                'blocks'=>'',
                'theme'=>'default',
                'date_published'=>date('Y-m-d H:i:s'),
                'in_site_map'=>true,
                'default_blocks'=>false
            ));
            if(!$page->save(true, false))
                return false;

            //404 Page
            $page = new Db_Object('Page');
            $page->setValues(array(
                'code'=>'404',
                'is_fixed'=>1,
                'html_title'=>'Error 404. Page not found',
                'menu_title'=>'404',
                'page_title'=>'We cannot find the page you are looking for.',
                'meta_keywords'=>'',
                'meta_description'=>'',
                'parent_id'=>null,
                'text' =>'We cannot find the page you are looking for.',
                'func_code'=>'',
                'order_no' => 2,
                'show_blocks'=>true,
                'published'=>true,
                'published_version'=>0,
                'editor_id'=>$userId,
                'date_created'=>date('Y-m-d H:i:s'),
                'date_updated'=>date('Y-m-d H:i:s'),
                'author_id'=>$userId,
                'blocks'=>'',
                'theme'=>'default',
                'date_published'=>date('Y-m-d H:i:s'),
                'in_site_map'=>false,
                'default_blocks'=>false
            ));
            if(!$page->save(true, false))
                return false;

            //API Page
            $page = new Db_Object('Page');
            $page->setValues(array(
                'code'=>'api',
                'is_fixed'=>1,
                'html_title'=>'API [System]',
                'menu_title'=>'API',
                'page_title'=>'API [System]',
                'meta_keywords'=>'',
                'meta_description'=>'',
                'parent_id'=>null,
                'text' =>'',
                'func_code'=>'api',
                'order_no' => 3,
                'show_blocks'=>false,
                'published'=>true,
                'published_version'=>0,
                'editor_id'=>$userId,
                'date_created'=>date('Y-m-d H:i:s'),
                'date_updated'=>date('Y-m-d H:i:s'),
                'author_id'=>$userId,
                'blocks'=>'',
                'theme'=>'default',
                'date_published'=>date('Y-m-d H:i:s'),
                'in_site_map'=>false,
                'default_blocks'=>false
            ));

            if(!$page->save(true, false))
                return false;

            return true;

        } catch (Exception $e){
            return false;
        }
    }
}
