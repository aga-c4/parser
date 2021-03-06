<?php
/**
 * User: AGA-C4
 * Date: 26.08.14
 * Time: 14:40
 */
 
/**
 * Parser Controller class - контроллер парсеров
 */
class ParserController{

    /**
     * @var string - Имя модуля контроллера (Внимание, оно должно соответствовать свойству $thisModuleName фронт контроллера модуля (используется во View)
     */
    public $thisModuleName = '';
    
    public function __construct($thisModuleName) {
        $this->thisModuleName = $thisModuleName;
    }

    /**
     * Метод по-умолчанию
     * @param string $tpl_mode - формат вывода
     * @param bool $console - если true, то вывод в консоль
     */
    public function action_index($tpl_mode='html', $console=false){
        $this->action_help($tpl_mode, $console);//Покажем хелп
    }

    /**
     * Вывод страницы помощи
     * @param string $tpl_mode - формат вывода
     * @param bool $console - если true, то вывод в консоль
     */
    public function action_help($tpl_mode='html', $console=false){
                    
        $help_txt = '
Parser module.

-------
Format:
php console.php [module=...] [controller=...] [action=...] [tpl_mode=...]
Don\'t print Space near "="

Modules:
parser - default module

Controllers:
parser - default controller

Actions:
help - read Help Information
parser - start parsing (only from console)

tpl_mode:
html - to view result as html file
txt  - to view result as txt file
json - to view result in json format

in html mode /out/nag/?from=test для забора файла
';

        //if ($tpl_mode=='html'){$help_txt = "<pre>$help_txt</pre>";}
        if ($tpl_mode=='html'){$help_txt = "Hello!";}

        //Установим глобальные метатеги для данной страницы
        Glob::$vars['page_title'] = 'Help'; //Метатег title
        Glob::$vars['page_keywords'] = 'Help'; //Метатег keywords
        Glob::$vars['page_description'] = 'Help'; //Метатег description
        Glob::$vars['page_h1'] = 'Help'; //Содержание основного заголовка страницы

        $item = array(); //Массив данных, передаваемых во View
        $item['page_content'] = $help_txt;

        //Запишем конфиг и логи----------------------
        SysBF::putFinStatToLog(); //Запишем конфиг и логи

        //View------------------------
        switch ($tpl_mode) {
            case "html": //Вывод в html формате для Web 
                require_once APP_MODULESPATH . 'default/view/main.php';
                break;
            case "txt": //Вывод в текстовом формате для консоли
                require_once APP_MODULESPATH . 'default/view/txtmain.php';
                break;
            case "json": //Вывод в json формате
                if (!Glob::$vars['console']) header('Content-Type: text/json; charset=UTF-8');
                echo Glob::$vars['json_prefix'] . json_encode($item);
                break;
        }
        
    }
    
    /**
     * Вывод файла с результатами
     * @param string $tpl_mode - формат вывода
     * @param bool $console - если true, то вывод в консоль
     */
    public function action_out($tpl_mode='html', $console=false){

        $help_txt = 'OUT!';

        if ($tpl_mode=='html'){$help_txt = "Hello!";}

        //Установим глобальные метатеги для данной страницы
        Glob::$vars['page_title'] = 'Help'; //Метатег title
        Glob::$vars['page_keywords'] = 'Help'; //Метатег keywords
        Glob::$vars['page_description'] = 'Help'; //Метатег description
        Glob::$vars['page_h1'] = 'Help'; //Содержание основного заголовка страницы

        $item = array(); //Массив данных, передаваемых во View
        $item['page_content'] = $help_txt;
        
        $fileOk = true;
        $refer = (!empty($_GET["from"]))?strtolower($_GET["from"]):''; //Название рефера передается в URL
        if ($refer!=='test') {
            SysLogs::addError('Error: wrong refer [' . $refer . ']');
            $fileOk = false;
        }
        
        $typeDnLoad = (!empty($_GET["dnloadto"]) && $_GET["dnloadto"]==='browser')?'browser':'file'; 
        
        //Откроем файл, откуда будем выгружать
        $filename = 'tmp/test-out.csv';
        $outputFileName = 'test-out.csv';
        $contentType = SysBf::mime_content_type($filename);

        if (!file_exists($filename)){//Если файл не существует, то
            SysLogs::addError('Error: not found file ['.$filename.']');
            $fileOk = false;
        }

        $fp = file_get_contents($filename);
        if ($fp===false){
            SysLogs::addError('Error: open file ['.$filename.']');
            $fileOk = false;
        }
        
        //Запишем конфиг и логи----------------------
        SysBF::putFinStatToLog(); //Запишем конфиг и логи
        
        //Выдадим ответ пользователю----------------
        if ($fileOk===false) { //Если ошибка, то сюда
            require_once APP_MODULESPATH . 'default/view/404.php';
        }else{
            //Отправим файл
            if($typeDnLoad == 'browser'){//Этим в браузер
                header("Content-Type: $contentType; charset=windows-1251;");
                header("Content-Disposition: inline; filename=".$outputFileName);
            }else{//Остальным - файл для скачивания
                header("Content-Type: application/force-download");
                header("Content-Disposition: attachment; filename=".$outputFileName);
            }
            echo $fp;
        }
        
    }

    /**
     * Парсинг сайта 
     * @param string $tpl_mode - формат вывода
     * @param bool $console - если true, то вывод в консоль
     */
    public function action_nagparser($tpl_mode='html', $console=false){
        
        require_once (USER_MODULESPATH . 'parser/model/ParserBf.class.php');  
        require_once (USER_MODULESPATH . 'parser/model/ParserPg.class.php'); 
        require_once (USER_MODULESPATH . 'parser/model/TestObj.class.php'); 
        require_once (USER_MODULESPATH . 'parser/model/TestObj2.class.php'); 
        require_once (USER_MODULESPATH . 'parser/model/TestObjList.class.php'); 

        $item = array('page_content'=>''); //Массив данных, передаваемых во View

        $continueOk = true;
        if (!Glob::$vars['console']) {
            $item['page_content'] = 'Run only from console!';
            $continueOk = false;
        }

        //Установим глобальные метатеги для данной страницы
        Glob::$vars['page_title'] = 'Test parser'; //Метатег title
        Glob::$vars['page_keywords'] = ''; //Метатег keywords
        Glob::$vars['page_description'] = 'Run script'; //Метатег description
        Glob::$vars['page_h1'] = 'Test parser'; //Содержание основного заголовка страницы
        
        $csvDelim = ';';

        if ($continueOk){ //Можем нормально работать
            $tpl_mode = 'txt';
            echo "Run Test parser\n";

            //Получи файл со списком артикулов и URL
            $srcPg = new ParserPg('https://shop.test-site.ru/search?count=500&in_stock=&page=1&search=cisco&show=all&sort=score_desc');
            echo "Status Code:" . $srcPg->status . "\n" ;
            $result = '';
            if ($srcPg->status!==200){
                echo $result = 'Error: Status Code:' . $srcPg->status . "\n";
            }else{
                $objList = new TestObjList($srcPg->content);
                echo "Found items:".count($objList->list)."\n";
                $result = date('c') . "\n" . implode($csvDelim, TestObj::getStru()) . "\n";
                SysBf::saveFile('tmp/nag-out.csv',$result);
                $counter = 0;
                foreach ($objList->list as $itemContent) {
                    $counter++;
                    sleep(1);
                    $parsObj = new TestObj($itemContent);
                    $resultStr = ''; //implode($csvDelim, $nagObj->data);
                    
                    $articul = (!empty($nagObj->data['articul']))?$parsObj->data['articul']:'';
                    $articul = trim(preg_replace("/cisco/i","",$parsObj->data['articul']));
                    $articul2 = preg_replace("/\([^\)]*\)/","",$articul);
                    $articul2 = strtolower(preg_replace("/[^A-Za-zА-Яа-я0-9]/i","",$articul2));
                    $resultStr .= $articul . $csvDelim . $articul2;
                    $resultStr .= $csvDelim . ((!empty($parsObj->data['original_id']))?$parsObj->data['original_id']:'');
                    $resultStr .= $csvDelim . ((!empty($parsObj->data['price_rub']))?$parsObj->data['price_rub']:'');
                    $resultStr .= $csvDelim . ((!empty($parsObj->data['price_usd']))?$parsObj->data['price_usd']:'');

                    //Доберем данные по отдельному объекту
                    if (!empty($nagObj->data['url'])){
                        $srcPg2 = new ParserPg($nagObj->data['url']);
                        $parsObj2 = new TestObj2($srcPg2->content);
                        $resultStr .= $csvDelim . ((!empty($parsObj2->data['price_rub1']))?$parsObj2->data['price_rub1']:'');
                        $resultStr .= $csvDelim . ((!empty($parsObj2->data['price_usd1']))?$parsObj2->data['price_usd1']:'');
                        $resultStr .= $csvDelim . ((!empty($parsObj2->data['price_rub2']))?$parsObj2->data['price_rub2']:'');
                        $resultStr .= $csvDelim . ((!empty($parsObj2->data['price_usd2']))?$parsObj2->data['price_usd2']:'');
                        $resultStr .= $csvDelim . ((!empty($parsObj2->data['price_rub3']))?$parsObj2->data['price_rub3']:'');
                        $resultStr .= $csvDelim . ((!empty($parsObj2->data['price_usd3']))?$parsObj2->data['price_usd3']:'');
                    }else{
                        $resultStr .= $csvDelim.$csvDelim.$csvDelim;
                    }
                    
                    $resultStr .= $csvDelim . ((!empty($parsObj->data['instock']))?$parsObj->data['instock']:'');
                    $resultStr .= $csvDelim . ((!empty($parsObj->data['url']))?$parsObj->data['url']:'');
                    
                    $resultStr .= "\n";
                    SysBf::saveFile('tmp/test-out.csv',SysBf::utw($resultStr),'a');
                    //$result .= $resultStr;
                    
                    if (0===$counter%10) echo "$counter\n";
                    
                    //Для тестов
                    //echo $resultStr;
                    //if ($counter>100) break;
                }
            }

            echo "Stop process!\n";

        }

        //Запишем конфиг и логи----------------------
        SysBF::putFinStatToLog();

        //View------------------------
        switch ($tpl_mode) {
            case "html": //Вывод в html формате для Web
                require_once APP_MODULESPATH . 'default/view/main.php';
                break;
            case "txt": //Вывод в текстовом формате для консоли
                require_once APP_MODULESPATH . 'default/view/txtmain.php';
                break;
            case "json": //Вывод в json формате
                if (!Glob::$vars['console']) header('Content-Type: text/json; charset=UTF-8');
                echo Glob::$vars['json_prefix'] . json_encode($item);
                break;
        }

    }
	
}

