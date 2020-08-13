################################################################################
##  Парсер - модуль для фреймворка FW.MNBV
################################################################################

Модуль парсера для фреймворка FW.MNBV

Последовательность установки:

1. Установите фреймворк mnbv.fw ( https://github.com/aga-c4/mnbv.fw ). 
 
2. Скачайте или склонируйте репозиторий с модулем в отдельную папку. Прилинкуйте
эту папку в папку модулей фреймворка "modules" с названием "modules/parser". 
Для этого в основной системе перейдите в папку "modules" и выполните примерно 
следующее
mklink /j parser D:\OSPanel\domains\feedgen\modules\parser

3. Добавьте модуль "parser" в список допустимых модулей Glob::$vars['module_alias']