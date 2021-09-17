<?php
declare(strict_types=1);

namespace Ajaxray\PHPWatermark\CommandBuilders;

use Ajaxray\PHPWatermark\Watermark;

class ImageCommandBuilder extends AbstractCommandBuilder implements WatermarkCommandBuilder
{

    /**
     * Build the imagemagick shell command for watermarking with Image
     *
     * @param string $markerImage The image file to watermark with
     * @param string $output The watermarked output file
     * @param array $options
     * @return string
     */
    public function getImageMarkCommand(string $markerImage, string $output, array $options): string
    {
        list($source, $destination) = $this->prepareContext($output, $options);
        $marker = escapeshellarg($markerImage);

        $anchor = $this->getAnchor();
        $offset = $this->getImageOffset();

        $tile = $this->getTile();
        $opacity = $this->getImageOpacity();

        return "composite -$anchor -$offset -$opacity $tile $marker $source $destination";
    }

    /**
     * Build the imagemagick shell command for watermarking with Text
     *
     * @param string $text The text content to watermark with
     * @param string $output The watermarked output file
     * @param array $options
     * @return string
     */
    public function getTextMarkCommand(string $text, string $output, array $options): string
    {
        list($source, $destination) = $this->prepareContext($output, $options);
        $text = escapeshellarg($text);

        $anchor = $this->getAnchor();
        $rotate = $this->getRotate();
        $font = $this->getFont();

        list($light, $dark) = $this->getDuelTextColor();
        list($offsetLight, $offsetDark) = $this->getDuelTextOffset();

        $draw = " -draw \"$rotate $anchor $light text $offsetLight $text $dark text $offsetDark $text\" ";

        if($this->isTiled()) {
            $size = $this->getTextTileSize();
            $command = "convert $size xc:none  $font -$anchor $draw miff:- ";
            $command .= " | composite -tile - $source  $destination";
        } else {
            $command = "convert $source $font $draw $destination";
        }

        return $command;
    }

    protected function getDuelTextColor()
    {
        return [
            "fill \"rgba\\(255,255,255,{$this->getOpacity()}\\)\"",
            "fill \"rgba\\(0,0,0,{$this->getOpacity()}\\)\"",
        ];
    }

    /**
     * @return string
     */
    protected function getRotate()
    {
        return empty($this->options['rotate']) ? '' : "rotate {$this->options['rotate']}";
    }

    /**
     * @return string
     */
    protected function getImageOpacity()
    {
        $strategy = (Watermark::STYLE_IMG_COLORLESS == $this->options['style']) ? 'watermark' : 'dissolve';
        return "$strategy ". ($this->options['opacity'] * 100) .'%';

    }

}
