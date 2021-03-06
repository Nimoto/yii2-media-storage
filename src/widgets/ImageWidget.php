<?php


namespace DevGroup\MediaStorage\widgets;

use DevGroup\DataStructure\models\Property;
use DevGroup\MediaStorage\components\GlideConfigurator;
use DevGroup\MediaStorage\helpers\MediaHelper;
use DevGroup\MediaStorage\models\Media;
use Yii;
use yii\base\Exception;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

class ImageWidget extends Widget
{
    const FIT_CONTAIN = 'contain';
    const FIT_MAX = 'max';
    const FIT_FILL = 'fill';
    const FIT_STRETCH = 'stretch';
    const FIT_CROP = 'crop';

    public $model = null;
    public $propertyId = null;


    public $width = null;
    public $height = null;
    /**
     * Sets how the image is fitted to its target dimensions
     * @see http://glide.thephpleague.com/1.0/api/size//#fit-fit
     * @var string
     */
    public $fit = self::FIT_CONTAIN;
    // @todo watermark
    public $quality = 90;
    public $blur = null;
    /**
     * Other configuration options from glide
     * Will overwrite original options,
     *     i.e. if $this->width = 80 and $this->config[w] = 100 result will be file.jpg?w=100
     * @see http://glide.thephpleague.com/1.0/api/quick-reference/
     */
    public $config = [];
    /**
     * @var string
     */
    public $outerViewFile = 'images';
    /**
     * @var string
     */
    public $singleViewFile = 'image';
    /**
     * @var null|int
     */
    public $limit = null;
    /**
     * @var int
     */
    public $offset = 0;
    /** @var array $additional Additional data passed to view */
    public $additional = [];

    public function init()
    {
        if (is_null($this->model)) {
            throw new Exception(Yii::t('devgroup.media-storage', 'Set model'));
        }
        if (is_null($this->propertyId)) {
            throw new Exception(Yii::t('devgroup.media-storage', 'Set property id'));
        }
        //@todo default config merge
    }

    public function run()
    {
        $property = Property::findById($this->propertyId);
        $configurator = new GlideConfigurator($this->width, $this->height, $this->fit, $this->quality, $this->config);
        $imageMedias = MediaHelper::getMediaData($this->model, $property, $configurator, $this->limit, $this->offset);

        return $this->render(
            $this->outerViewFile,
            [
                'medias' => $imageMedias,
                'singleViewFile' => $this->singleViewFile,
                'additional' => $this->additional,
            ]
        );
    }


}
