<?
namespace Wbs24;

require_once('autoload.php');

use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Wbs24\Sbermmexport\{
    Admin,
    DetailSettings,
    CommonPrice,
    ExtendPrice,
    ExtendPriceByFormula,
    CommonWarehouse,
    ExtendWarehouse,
    PropertiesBasedWarehouse,
    Filter,
    Limit,
    Tax,
    Shipment,
    Wrappers,
    Legasy,
    StringTemplate,
    BasicAuth
};

class Sbermmexport extends Legasy
{
    public const MAX_MANUAL_ELEMENTS = 1000;
    protected const MANUAL_CALL_FLAG = 'WBS24_SBERMMEXPORT_MANUAL_CALL';

    protected $adminObject;
    protected $detailSettingsObject;
    protected $priceObject;
    protected $filterObject;
    protected $taxObject;
    protected $shipmentObject;

    protected $StringTemplate;

    protected $needProperties = false;
    protected $iblockIdsToProperties = [];

    public function __construct($objects = [])
    {
        $this->StringTemplate = $objects['StringTemplate'] ?? new StringTemplate();
    }

    public function loadPropertiesForOfferIds($offersInfo, $properties)
    {
        $setId = $offersInfo['setId'] ?? 'ID';
        $setOfferId = $offersInfo['setOfferId'] ?? 'ID';
        $iblockId = $offersInfo['iblockId'] ?? false;
        $offerIdWithoutProperties = [
            'ID',
            'XML_ID',
        ];

        if (!in_array($setId, $offerIdWithoutProperties)) {
            $propertyId = $this->getPropertyIdByCode($setId, $iblockId);
            if ($propertyId && !in_array($propertyId, $properties)) {
                $properties[] = $propertyId;
            }
        }

        if (!in_array($setOfferId, $offerIdWithoutProperties)) {
            $offersIblockId = $this->getOffersIblockId($iblockId);
            $propertyId = $this->getPropertyIdByCode($setOfferId, $offersIblockId);
            if ($propertyId && !in_array($propertyId, $properties)) {
                $properties[] = $propertyId;
            }
        }

        return $properties;
    }

    public function getOffersIblockId($iblockId)
    {
        $offersInfo = \CCatalogSku::GetInfoByProductIBlock($iblockId);

        return $offersInfo['IBLOCK_ID'] ?? false;
    }

    public function getElementId($element, $code = 'ID')
    {
        $id = false;

        switch ($code) {
            case "ID":
                $id = $element[$code] ?? false;
                break;
            case "XML_ID":
                $code = 'EXTERNAL_ID';
                $id = $element[$code] ?? false;
                break;
            default:
                $propertyId = $this->getPropertyIdByCode($code, $element['IBLOCK_ID']);
                $id = $element['PROPERTIES'][$propertyId]['~VALUE'] ?? false;
                if ($id) $id = htmlspecialchars($id);
        }

        return $id;
    }

    protected function getPropertyIdByCode($code, $iblockId)
    {
        $properties = $this->getPropertiesByIblockId($iblockId);
        $propertyId = $properties[$code] ?? false;

        return $propertyId;
    }

    protected function getPropertiesByIblockId($iblockId)
    {
        $properties = $this->iblockIdsToProperties[$iblockId] ?: [];
        if ($properties) return $properties;

        $res = \CIBlockProperty::GetList([], [
            'IBLOCK_ID' => $iblockId,
        ]);
        while ($field = $res->Fetch()) {
            $propertyCode = $field['CODE'];
            $propertyId = $field['ID'];
            $properties[$propertyCode] = $propertyId;
        }

        $this->iblockIdsToProperties[$iblockId] = $properties;

        return $properties;
    }

    public function getParams($blob)
    {
        $param = [];
        foreach ($blob as $name => $value) {
            $calcValue = $value;
            if ($value == 'Y') $calcValue = true;
            if ($value == 'N') $calcValue = false;
            $param[$name] = $calcValue;
        }

        return $param;
    }

    public function updateNeedProperties($needPropertiesIds, $settings)
    {
        foreach ($settings as $key => $value) {
            if (is_numeric($key)) {
                $needPropertiesIds[$key] = true;
            } elseif ($key == 'PRODUCT_NAME' || $key == 'OFFER_NAME') {
                $prorertyIds = $this->getPropertyIdsFromTemplate($value);
                foreach ($prorertyIds as $id) {
                    $needPropertiesIds[$id] = true;
                }
            } elseif (
                $key == 'PRODUCT_PACKAGE_RATIO_PROPERTY'
                || $key == 'OFFER_PACKAGE_RATIO_PROPERTY'
                || $key == 'PRODUCT_OLD_PRICE_PROPERTY'
                || $key == 'OFFER_OLD_PRICE_PROPERTY'
            ) {
                $needPropertiesIds[$value] = true;
            }
        }

        if ($needPropertiesIds) $this->needProperties = true;

        return $needPropertiesIds;
    }

    public function getUrl($element, $settings)
    {
        $url = false;
        $isOffer = $this->isOffer($element);
        $propertyId = $isOffer ? $settings['OFFER_URL'] : $settings['PRODUCT_URL'];
        if ($propertyId) {
            $url = $element['PROPERTIES'][$propertyId]['VALUE'] ?? false;
            $url = htmlspecialcharsbx($url);
        }

        return $url;
    }

    public function getName($element, $settings, $parentProperties = [])
    {
        $isOffer = $this->isOffer($element);
        $template = $isOffer ? $settings['OFFER_NAME'] : $settings['PRODUCT_NAME'];
        if (!$template) $template = $element['NAME'];
        $properties = $element['PROPERTIES'] ?? [];
        foreach ($parentProperties as $k => $prop) $properties[$k] = $prop;
        $markValues = $this->addPropertiesToMarkValues(['NAME' => $element['NAME']], $template, $properties);

        return $this->StringTemplate->getStringByTemplate($template, $markValues, $element['NAME']);
    }

    protected function isOffer($element)
    {
        return ($element['TYPE'] == 4);
    }

    protected function addPropertiesToMarkValues($markValues, $template, $properties)
    {
        if (strpos($template, '{PROPERTY_') !== false) {
            $propertyIds = $this->getPropertyIdsFromTemplate($template);
            foreach ($propertyIds as $id) {
                $property = $properties[$id] ?? false;
                $value = $this->getPropertyValue($property);
                if (is_array($value)) $value = current($value);
                $markValues['PROPERTY_'.$id] = $value ?: '';
            }
        }

        return $markValues;
    }

    protected function getPropertyValue($property)
    {
        $value = $property['VALUE'] ?? '';
        $type = $property['PROPERTY_TYPE'] ?? false;
        if ($type == 'E' && is_numeric($value)) {
            $id = intval($value);
            $element = \CIBlockElement::GetList([], ['=ID' => $id], false, false, ['ID', 'NAME'])->Fetch();
            $value = $element['NAME'] ?? '';
        }

        return $value;
    }

    protected function getPropertyIdsFromTemplate($template)
    {
        preg_match_all('/\{PROPERTY_(\d+)\}/', $template, $matches);
        $propertyIds = $matches[1] ?? [];

        return $propertyIds;
    }

	public function getAdminObject($param = [])
	{
		return $this->adminObject = new Admin($param);
	}

    public function getDetailSettings()
    {
        return $this->detailSettingsObject = new DetailSettings();
    }

	public function getPriceObject($param)
	{
		$extendPrice = $param['extendPrice'] ?: false;
        $extendPriceByFormula = $param['extendPriceByFormula'] ?: false;

        if ($extendPriceByFormula) {
            $this->priceObject = new ExtendPriceByFormula($param);
        } elseif ($extendPrice) {
			$this->priceObject = new ExtendPrice($param);
		} else {
			$this->priceObject = new CommonPrice($param);
		}

		return $this->priceObject;
	}

    public function getWarehouseObject($param)
    {
        $extendWarehouse = $param['extendWarehouse'] ?: false;
        $propertiesBasedWarehouse = $param['propertiesBasedWarehouse'] ?: false;

        if ($extendWarehouse && !$propertiesBasedWarehouse) {
            $this->warehouseObject = new ExtendWarehouse($param);
        } elseif ($propertiesBasedWarehouse) {
            $this->warehouseObject = new PropertiesBasedWarehouse($param);
        } else {
            $this->warehouseObject = new CommonWarehouse($param);
        }

        if (!$this->needProperties) {
            $this->needProperties = $this->warehouseObject->checkNeedProperties();
        }

        return $this->warehouseObject;
    }

	public function getLimitationObject($param)
	{
		return $this->limitationObject = new Limit($param);
	}

    public function getFilterObject($param)
	{
		return $this->filterObject = new Filter($param);
	}

    public function getTaxObject(array $param)
	{
		return $this->taxObject = new Tax($param);
	}

    public function getShipmentObject(array $param)
    {
        return $this->shipmentObject = new Shipment($param);
    }

    public function setParamToBasicAuthObject($param)
    {
        $this->basicAuthObject = new BasicAuth($param);
    }

    public function checkNeedProperties()
    {
        return $this->needProperties;
    }

	public function cleanKeysFromQuotes($array)
	{
		$cleanArray = [];
		if (is_array($array)) {
			foreach($array as $key => $value) {
				$key = str_replace(["'", '"'], '', $key);
				$cleanArray[$key] = $value;
			}
		}

		return $cleanArray;
	}

    // не используется
    public function appendFile($sourceFile, $destinationFile)
    {
        $handle = fopen($sourceFile, "rb");
        $handleTo = fopen($destinationFile, "ab");

        $result = false;
        if ($handle && $handleTo) {
            $result = true;
            while (!feof($handle)) {
                $line = fgets($handle, 4096);
                if (!$line) continue;
                $result = fwrite($handleTo, $line);
                if ($result === false) break;
            }
        }

        fclose($handleTo);
        fclose($handle);

        return $result;
    }

    public function updateExportFile($exportFile, $exportNewLinkedFile)
    {
        $success = false;
        $fullPathExportFile = $_SERVER["DOCUMENT_ROOT"].$exportFile;
        $fullPathExportNewLinkedFile = $_SERVER["DOCUMENT_ROOT"].$exportNewLinkedFile;

        $phpCode = $this->basicAuthObject->getAuthConditionCode($fullPathExportNewLinkedFile);

        $success = $this->writeToFile(
            $fullPathExportFile,
            $phpCode."\n"
        );

        if ($success) {
            $this->writeToFile(
                $fullPathExportFile.'.txt',
                $fullPathExportNewLinkedFile
            );

            $this->deleteOldExportsBeforeCurrent($fullPathExportNewLinkedFile);
        }

        return $success;
    }

    protected function writeToFile($fullPath, $data)
    {
        $success = false;

        $fp = @fopen($fullPath, "wb");
        if ($fp) {
            fwrite($fp, $data);
            fclose($fp);
            $success = true;
        }

        return $success;
    }

    protected function getFromFile($fullPath)
    {
        return file_get_contents($fullPath);
    }

    protected function deleteOldExportsBeforeCurrent($currentFile)
    {
        $filesList = $this->getFilesListSamePath($currentFile);
        $similarFilesList = $this->getFilesByExample($filesList, $currentFile);
        $exportTime = $this->getExportTime($currentFile);
        $oldFilesList = $this->getFilesBeforeTime($similarFilesList, $exportTime);
        foreach ($oldFilesList as $file) {
            $this->deleteFile($file);
        }
    }

    protected function getFilesListSamePath($currentFile)
    {
        $path = dirname($currentFile);
        $filesList = scandir($path);
        $filesListWithPath = [];
        foreach ($filesList as $file) {
            $filesListWithPath[] = $path.'/'.$file;
        }

        return $filesListWithPath;
    }

    protected function getFilesByExample($filesList, $example)
    {
        $similarFilesList = [];
        $separator = '.php_import_';
        list($exampleStartName) = explode($separator, $example);

        foreach ($filesList as $file) {
            if (strpos($file, $separator) === false) continue;

            list($startName, $finishName) = explode($separator, $file);
            if ($startName == $exampleStartName) {
                $similarFilesList[] = $file;
            }
        }

        return $similarFilesList;
    }

    protected function getExportTime($currentFile)
    {
        $separator = '.php_import_';
        if (strpos($currentFile, $separator) === false) return;

        list($startName, $finishName) = explode($separator, $currentFile);
        $time = str_replace(['_', '.php'], '', $finishName);

        return intval($time);
    }

    protected function getFilesBeforeTime($filesList, $beforeTimestamp)
    {
        $oldFilesList = [];

        foreach ($filesList as $file) {
            $fileTime = $this->getExportTime($file);
            if (!$fileTime) continue;

            if ($fileTime < $beforeTimestamp) {
                $oldFilesList[] = $file;
            }
        }

        return $oldFilesList;
    }

    protected function deleteFile($file)
    {
        if ($file) unlink($file);
    }

    public function isLimitOfElementsExpired($trace = false)
    {
        $this->elementCounter++;
        $needLimit = ($this->isManualCall($trace) && $this->isDemoMode());

        return ($needLimit && $this->elementCounter > self::MAX_MANUAL_ELEMENTS);
    }

    protected function isManualCall($trace = false)
    {
        if ($this->manualCall !== null) return $this->manualCall;
        if (!$trace) $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $manualCall = false;
        foreach ($trace as $info) {
            $file = $info['file'] ?? '';
            if (strpos($file, 'export_setup.php') !== false) {
                $manualCall = true;
                break;
            }
        }

        $this->manualCall = $manualCall;
        $_SESSION[self::MANUAL_CALL_FLAG] = 'Y';

        return $manualCall;
    }

    public function isDemoMode()
    {
        return (Loader::includeSharewareModule('wbs24.sbermmexport') == Loader::MODULE_DEMO);
    }

    public static function OnPrologHandler() {
        $sber = new Sbermmexport();
        if ($sber->isFinishManualExport() && $sber->isDemoMode()) {
            \Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/wbs24.sbermmexport/init.js");
        }
    }

    public function isFinishManualExport()
    {
        $finish = false;
        if (
            strpos($_SERVER['REQUEST_URI'], '/bitrix/admin/cat_export_setup.php') !== false
            && isset($_SESSION[self::MANUAL_CALL_FLAG])
            && $_SESSION[self::MANUAL_CALL_FLAG] == 'Y'
        ) {
            $finish = true;
            unset($_SESSION[self::MANUAL_CALL_FLAG]);
        }

        return $finish;
    }
}
?>
