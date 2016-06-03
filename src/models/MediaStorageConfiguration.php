<?php


namespace DevGroup\MediaStorage\models;

use DevGroup\ExtensionsManager\models\BaseConfigurationModel;
use DevGroup\MediaStorage\MediaModule;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class MediaStorageConfiguration extends BaseConfigurationModel
{

    public function rules()
    {
        return [
            ['activeFS', 'isArray'],
        ];
    }

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        $attributes = [
            'activeFS',
        ];

        parent::__construct($attributes, $config);
        /** @var MediaModule $module */
        $module = MediaModule::getModuleInstance();
        $this->activeFS = $module->activeFS;
    }

    public function isArray($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, "The $attribute must be array");
        }
    }

    public function attributeLabels()
    {
        return [
            'defaultThumbnailSize' => Yii::t('app', 'Default thumbnail size'),
            'noImageSrc' => Yii::t('app', 'No image src'),
            'thumbnailsDirectory' => Yii::t('app', 'Thumbnails directory'),
            'useWatermark' => Yii::t('app', 'Use watermark'),
            'watermarkDirectory' => Yii::t('app', 'Watermark directory'),
            'components' => Yii::t('app', 'Components'),
        ];
    }

    /**
     * Returns array of module configuration that should be stored in application config.
     * Array should be ready to merge in app config.
     * Used for web only.
     *
     * @return array
     */
    public function webApplicationAttributes()
    {
        return [];
    }

    /**
     * Returns array of module configuration that should be stored in application config.
     * Array should be ready to merge in app config.
     * Used for console only.
     *
     * @return array
     */
    public function consoleApplicationAttributes()
    {
        return [
            'controllerMap' => ['media' => 'DevGroup\MediaStorage\commands'],
        ];
    }

    /**
     * Returns array of module configuration that should be stored in application config.
     * Array should be ready to merge in app config.
     * Used both for web and console.
     *
     * @return array
     */
    public function commonApplicationAttributes()
    {
        $attributes = $this->getAttributesForStateSaving();
        $allFsConfig = ArrayHelper::index(ArrayHelper::getValue($attributes, 'activeFS', []), 'name');

        $active = [];
        $allFs = [];
        foreach ($allFsConfig as $attribute) {
            $fs = $attribute;
            foreach (ArrayHelper::remove($fs, 'necessary', []) as $key => $item) {
                $fs[$key] = $item;
            }
            foreach (ArrayHelper::remove($fs, 'unnecessary', []) as $key => $item) {
                if ($item !== '') {
                    $fs[$key] = $item;
                }
            }
            $name = ArrayHelper::remove($fs, 'name');
            unset($fs['options']);
            if (ArrayHelper::getValue($attribute, 'options.0', false)) {
                $active[] = $name;
            }
            $allFs[$name] = $fs;
        }
        return ArrayHelper::merge(
            [
                'components' => $allFs,
                'modules' => [
                    'media' => $attributes,
                ],
                'params' => [
                    'activeFsNames' => $active,
                ],
            ],
            [
                'components' => [
                    'urlManager' => [
                        'excludeRoutes' => ['media/file/send', 'media/file/xsend'],
                    ],
                ],
                'modules' => ['media' => ['class' => MediaModule::class]],
            ]
        );
    }

    /**
     * Returns array of key=>values for configuration.
     *
     * @return mixed
     */
    public function appParams()
    {
        return [
            'flysystemDefaultConfigs' => [
                'fs' => [
                    'class' => 'creocoder\flysystem\LocalFilesystem',
                    'necessary' => [
                        'path' => '@app/media',
                        'srcAdapter' => 'app\modules\image\components\Local',
                    ],
                ],
                'ftpFs' => [
                    'class' => 'creocoder\flysystem\FtpFilesystem',
                    'necessary' => [
                        'host' => 'ftp.example.com',
                        'srcAdapter' => 'app\modules\image\components\Ftp',
                    ],
                    'unnecessary' => [
                        'port' => '',
                        'username' => '',
                        'password' => '',
                        'ssl' => '',
                        'timeout' => '',
                        'root' => '',
                        'permPrivate' => '',
                        'permPublic' => '',
                        'passive' => '',
                        'transferMode' => '',
                    ],
                ],
                'awss3Fs' => [
                    'class' => 'creocoder\flysystem\AwsS3Filesystem',
                    'necessary' => [
                        'key' => 'your-key',
                        'secret' => 'your-secret',
                        'bucket' => 'your-bucket',
                        'srcAdapter' => 'app\modules\image\components\Awss3',
                    ],
                    'unnecessary' => [
                        'region' => '',
                        'baseUrl' => '',
                        'prefix' => '',
                        'options' => '',
                    ],
                ],
                'sftpFs' => [
                    'class' => 'creocoder\flysystem\SftpFilesystem',
                    'necessary' => [
                        'host' => 'sftp.example.com',
                        'username' => 'your-username',
                        'password' => 'your-password',
                        'srcAdapter' => 'app\modules\image\components\Ftp',
                    ],
                    'unnecessary' => [
                        'port' => '',
                        'privateKey' => '',
                        'timeout' => '',
                        'root' => '',
                        'permPrivate' => '',
                        'permPublic' => '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns array of aliases that should be set in common config
     * @return array
     */
    public function aliases()
    {
        return ['@DevGroup/MediaStorage' => realpath(dirname(__DIR__)),];
    }

}