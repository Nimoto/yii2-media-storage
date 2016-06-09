<?php

namespace DevGroup\MediaStorage\helpers;

use creocoder\flysystem\Filesystem;
use DevGroup\MediaStorage\MediaModule;
use DevGroup\MediaStorage\models\Media;
use Yii;
use DevGroup\MediaStorage\models\MediaGroup;
use yii\base\Exception;
use yii\base\Object;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class MediaHelper extends Object
{

    /**
     * @return Filesystem
     */
    public static function getProtectedFilesystem()
    {
        return Yii::$app->protectedFilesystem;
    }


    public static function getFsDefaultCfg($number = '')
    {
        $result = Yii::$app->params['flysystemDefaultConfigs'];
        if (is_int($number)) {
            $configured = MediaModule::getModuleInstance()->activeFS[$number];
            $result = [ArrayHelper::merge($result[self::getFsCfgDropdown()[$configured['class']]], $configured)];
        }
        return $result;
    }

    public static function getFsCfgDropdown()
    {
        $cfg = self::getFsDefaultCfg();
        $res = [];
        foreach ($cfg as $name => $item) {
            $res[$item['class']] = $name;
        }
        return $res;
    }

    public static function getConfigurationTpl($form, $model, $number = '{{number}}')
    {
        $res = [];
        $cfg = self::getFsDefaultCfg($number);
        foreach ($cfg as $name => $item) {
            $necessaryContent = $form->field($model, "activeFS[{$number}][urlRule]")->textInput(
                ['value' => $item['urlRule']]
            )->label('urlRule');
            foreach (ArrayHelper::getValue($item, 'necessary', []) as $necessaryConfName => $necessaryConfVal) {
                $content = $form->field(
                    $model,
                    "activeFS[{$number}][necessary][{$necessaryConfName}]"
                )->textInput(['value' => $necessaryConfVal])->label($necessaryConfName);
                $necessaryContent .= $content;
            }
            $unnecessaryContent = '';
            foreach (ArrayHelper::getValue($item, 'unnecessary', []) as $unnecessaryConfName => $unnecessaryConfVal) {
                $unnecessaryContent .= $form->field(
                    $model,
                    "activeFS[{$number}][unnecessary][{$unnecessaryConfName}]"
                )->textInput(['value' => $unnecessaryConfVal])->label($unnecessaryConfName);
            }
            $res[$item['class']] = Tabs::widget(
                [
                    'items' => [
                        ['label' => Yii::t('app', 'necessary'), 'content' => $necessaryContent],
                        ['label' => Yii::t('app', 'unnecessary'), 'content' => $unnecessaryContent],
                    ],
                ]
            );
        }
        return Json::encode($res);
    }

    public static function loadRoots()
    {
        $configuredFSnames = ArrayHelper::getValue(Yii::$app->params, 'activeFsNames', []);
        if (count($configuredFSnames) === 0) {
            return [
                'baseRoot' => [
                    'class' => 'mihaildev\elfinder\flysystem\Volume',
                    'component' => 'protectedFilesystem',
                    'name' => 'protected',
                    'options' => [
                        'attributes' => [
                            [
                                'pattern' => '#.*(\.tmb|\.quarantine)$#i',
                                'read' => false,
                                'write' => false,
                                'hidden' => true,
                                'locked' => false,
                            ],
                            [
                                'pattern' => '#.+[^/]$#',
                                'read' => false,
                                'write' => true,
                                'hidden' => true,
                                'locked' => false,
                            ],
                        ],
                        'uploadOverwrite' => false,
                    ],

                ],
            ];
        }
        $res = [];
        foreach ($configuredFSnames as $configuredFSname) {
            $res[$configuredFSname] = [
                'class' => 'mihaildev\elfinder\flysystem\Volume',
                'component' => $configuredFSname,
                'name' => $configuredFSname,
                'options' => [
                    'attributes' => static::loadAttrs(),
                    'uploadOverwrite' => false,
                ],
            ];
        }
        return $res;
    }

    public static function loadAttrs($where = null)
    {
        $ini = ArrayHelper::merge(
            [
                [
                    'pattern' => '#.*(\.tmb|\.quarantine)$#i',
                    'read' => false,
                    'write' => false,
                    'hidden' => true,
                    'locked' => false,
                ],
                [
                    'pattern' => '#.+[^/]$#',
                    'read' => false,
                    'write' => true,
                    'hidden' => true,
                    'locked' => false,
                ],
            ],
            static::loadMediasAttrs($where)
        );
        return $ini;
    }

    public static function loadMediasAttrs($where = null)
    {
        $mediasQuery = Media::find();
        if (is_null($where) === false) {
            $mediasQuery->where(['id' => $where]);
        }
        $medias = $mediasQuery->all();
        $result = array_reduce(
            $medias,
            function ($total, $item) {
                /**
                 * @var Media $item
                 */
                $total[] = [
                    'pattern' => '#^/' . preg_quote($item->path) . '$#',
                    'read' => true,
                    'write' => true,
                    'hidden' => false,
                    'locked' => false,
                ];
                return $total;
            },
            []
        );
        return $result;

    }

    public static function compileUrl($mediaId)
    {

    }
}
