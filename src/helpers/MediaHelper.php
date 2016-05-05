<?php

namespace DevGroup\MediaStorage\helpers;

use Yii;
use DevGroup\MediaStorage\models\MediaGroup;
use yii\base\Exception;
use yii\base\Object;

class MediaHelper extends Object
{
    private static $upl_dir = null;
    private static $tmp_dir = null;
    private static $storage_folder_name = '/media-storage/';

    public static function getProtectedFilesystem()
    {
        return Yii::$app->protectedFilesystem;
    }

    public static function getPublicFilesystem()
    {
        return Yii::$app->publicFilesystem;
    }

    public static function getTmpDir()
    {
        if (self::$tmp_dir === null) {
            self::$tmp_dir = Yii::getAlias('@runtime') . self::$storage_folder_name;

            if (!file_exists(self::$tmp_dir)) {
                mkdir(self::$tmp_dir, 0755, true);
            }
        }

        return self::$tmp_dir;
    }

    public static function getUploadDir()
    {
        if (self::$upl_dir === null) {
            self::$upl_dir = self::$storage_folder_name . date('Y/m/');

            if (!Yii::$app->fs->has(self::$upl_dir)) {
                Yii::$app->fs->createDir(self::$upl_dir);
            }
        }

        return self::$upl_dir;
    }

    public static function getWidgetInputName()
    {
        return 'media-item-id';
    }

    /**
     * Return all media groups for using as dropdown items
     *
     * @return array Dropdown items format
     */
    public static function getMediaGroupsForDropdown()
    {
        $media_groups = [];

        foreach (MediaGroup::find()->select(['id', 'name'])->orderBy(['name' => SORT_ASC])->all() as $group) {
            $media_groups[] = [
                'label' => $group->name,
                'url' => '#',
                'linkOptions' => ['data-val' => $group->id, 'class' => 'js-link'],
            ];
        }

        return $media_groups;
    }

    /**
     * Return roles and permission items for using in Select2 widget
     *
     * @return array Roles and permission items
     */
    public static function getPermissionsForSelect()
    {
        $get_names = function ($item) {
            return $item->name;

        };

        return [
            'Roles' => array_map($get_names, Yii::$app->authManager->getRoles()),
            'Items' => array_map($get_names, Yii::$app->authManager->getPermissions()),
        ];
    }

    public static function saveFileFromLocalToFlysystem($filePath, $flysystem)
    {
        $stream = fopen($filePath, 'r+');
        $filename = basename($filePath);
        if ($flysystem->has($filename)) {
            $tmp = explode('.', $filename);
            $extension = array_pop($tmp);
            $filename = implode('.', $tmp) . '-' . uniqid() . '.' . $extension;
        }
        if ($flysystem->putStream($filename, $stream)) {
            return $filename;
        }else{
            throw new Exception(Yii::t('app', 'File not saved'));
        }
    }

    public static function removeTmpFile($filePath)
    {
        unlink($filePath);
    }
}
