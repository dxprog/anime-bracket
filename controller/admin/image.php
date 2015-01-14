<?php

namespace Controller\Admin {

    use Lib;
    use stdClass;

    class Image extends \Controller\Me {

        public static function generate(array $params) {
            $action = array_shift($params);
            switch ($action) {
                case 'upload':
                    self::_uploadFile();
                    break;
                case 'crop':
                    self::_cropImage();
                    break;
            }
        }

        private static function _uploadFile() {

            $out = new stdClass;
            $out->success = false;
            $out->message = 'Unable to upload image';

            $fileName = './cache/' . uniqid();
            $tmpFile = $_FILES['upload']['tmp_name'];
            if (is_uploaded_file($tmpFile) && move_uploaded_file($tmpFile, $fileName)) {
                $type = Lib\ImageLoader::getImageType($fileName);
                if ($type) {
                    rename($fileName, $fileName . '.' . $type);
                    $out->success = true;
                    $out->fileName = str_replace('.', '', $fileName) . '.' . $type;
                } else {
                    unlink($fileName);
                    $out->message = 'Invalid image';
                }
            }

            Lib\Display::renderJson($out);

        }

        private static function _cropImage() {

            $out = new stdClass;
            $out->success = false;
            $out->message = 'Unable to crop image';

            $imageFile = Lib\Url::Post('imageFile');
            $x = Lib\Url::Post('x', true);
            $y = Lib\Url::Post('y', true);
            $width = Lib\Url::Post('width', true);
            $height = Lib\Url::Post('height', true);

            if ($imageFile && null !== $x && null !== $y && null !== $width && null !== $height) {
                $imageFile = $imageFile{0} === '/' ? '.' . $imageFile : $imageFile;
                $image = Lib\ImageLoader::loadImage($imageFile);
                if ($image) {
                    $image = self::_sizeUp($image->image);
                    $croppedImage = imagecreatetruecolor(BRACKET_IMAGE_SIZE, BRACKET_IMAGE_SIZE);
                    imagecopyresampled($croppedImage, $image, 0, 0, $x, $y, BRACKET_IMAGE_SIZE, BRACKET_IMAGE_SIZE, $width, $height);
                    $fileName = '/cache/' . md5($imageFile) . '.jpg';
                    imagejpeg($croppedImage, '.' . $fileName);
                    imagedestroy($image);
                    imagedestroy($croppedImage);
                    $out->success = true;
                    $out->fileName = $fileName;
                }
            } else {
                $out->message = 'Parameters missing';
            }

            Lib\Display::renderJson($out);

        }

        private static function _sizeUp($image) {
            $width = imagesx($image);
            $height = imagesy($image);
            if ($width > MAX_WIDTH || $height > MAX_HEIGHT) {
                $newHeight = 0;
                $newWidth = 0;
                if ($width > $height) {
                    $ratio = $height / $width;
                    $newWidth = MAX_WIDTH;
                    $newHeight = $newWidth * $ratio;
                } else {
                    $ratio = $width / $height;
                    $newHeight = MAX_HEIGHT;
                    $newWidth = $newHeight * $ratio;
                }

                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $newImage;
            }
            return $image;
        }

    }

}