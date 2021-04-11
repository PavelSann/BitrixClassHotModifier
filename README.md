# BitrixClassHotModifier

Утилитный класс, который позволяет подменять классы битрикс, загружаемые через class loader на их модифицированных наследников.
Работает на основе spl_autoload_register и php_user_filter

# Пример использования

1.  Скопировать в дирректорию /local/classmodifier
2.  Добавить local/classmodifier/init.php в local/php_interface/init.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/classmodifier/init.php';
3.  Создать файл с именем соотвествующем файлу заменяемого класса, например 
/local/classmodifier/modules/modules/crm/classes/general/restservice.php 
оригинал /bitrix/modules/crm/classes/general/restservice.php
4.  В начале созданного файла добавить комментарий с указанием файла-оригинала
@CLASS_FILE modules/crm/classes/general/restservice.php
5.  Описать модифицированный класс как наследника оригинала добавив к имени __
class CCrmRequisiteRestProxy__ extends CCrmRequisiteRestProxy {
...
}
6.  Добавить запись в файл local/classmodifier/init.php 
Для файла с одним классом: 
ClassHotModifier::setClasses(['CCrmDeal'], 'modules/crm/classes/mysql/crm_deal.php');
Для файла с несколькими классами:
ClassHotModifier::setClassesExt(
		[
			'CCrmRestService',
			'ICrmRestProxy',
			'CCrmRestEventDispatcher',
			'CCrmLiveFeedMessageRestProxy',
		], [
			'CCrmRequisiteRestProxy',
			'CCrmRequisiteLinkRestProxy',
			'CCrmRequisiteBankDetailRestProxy',
			'CCrmAddressRestProxy',
			'CCrmDealRestProxy',
			'CCrmContactRestProxy',
			'CCrmCompanyRestProxy',
			'CCrmProductRestProxy',
			'CCrmProductRowRestProxy',
		], 'modules/crm/classes/general/restservice.php');
    
    
 