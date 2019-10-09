<?php
namespace humhub\modules\onlinedrives\models;

use Yii;
use humhub\modules\onlinedrives\Module;

/**
 * ConfigureForm defines the configurable fields.
 *
 * @package humhub\modules\onlinedrives\models
 * @author Sebastian Stumpf
 */
class ConfigureForm extends \yii\base\Model
{
    public $disableZipSupport;
    public $uploadBehaviour;

    public function init()
    {
        parent::init();
        $module = $this->getModule();
        $this->disableZipSupport = !$module->isZipSupportEnabled();
        $this->uploadBehaviour = $module->getUploadBehaviour();
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return Yii::$app->getModule('onlinedrives');
    }

    /**
     * Declares the validation rules.
     */
    public function rules()
    {
        return [
            ['disableZipSupport', 'boolean'],
            ['uploadBehaviour', 'integer'],
        ];
    }

    /**
     * Declares customized attribute labels.
     * If not declared here, an attribute would have a label that is
     * the same as its name with the first letter in upper case.
     */
    public function attributeLabels()
    {
        return [
            'disableZipSupport' => Yii::t('OnlinedrivesModule.base', 'Disable archive (ZIP) support'),
            'uploadBehaviour' => Yii::t('OnlinedrivesModule.base', 'Upload behaviour for existing file names'),
        ];
    }

    public function attributeHints()
    {
        return [
            'uploadBehaviour' => Yii::t('OnlinedrivesModule.base', '<strong>Note:</strong> The replacement behaviour is currently not supported for zip imports.')
        ];
    }

    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $module = $this->getModule();
        $module->settings->set('disableZipSupport', $this->disableZipSupport);
        $module->settings->set('uploadBehaviour', $this->uploadBehaviour);

        return true;
    }
}
?>