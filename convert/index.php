<?php

use pocketmine\world\format\io\FormatConverter;
use pocketmine\world\format\io\leveldb\LevelDB;
use pocketmine\world\format\io\WorldProvider;
use pocketmine\world\format\io\WorldProviderManager;
use pocketmine\world\generator\GeneratorManager;

require_once '../pmmp/vendor/autoload.php';

function createAPIObject(bool $success, string $message, ?string $dl = null): array {
    return $success ? [
            'result' => true,
            'msg' => $message,
            'dl' => $dl
    ] : [
            'result' => false,
            'err_msg' => $message
    ];
}


$api = createAPIObject(false, "알 수 없는 오류입니다.");

$upload = $_FILES['file'];
$user_id = $_POST['user_id'];

$worlds_dir = realpath("../worlds/");

$target_path = ($target_dir = $worlds_dir . "/downloads/" . ($s = substr(base64_encode(random_bytes(20)), 3, 10))) . "/" . ($name = $upload['name']);

@mkdir($target_dir, 0777, true);

define("LOG_DIR", $log_dir = realpath("../convert/progress/") . "/" . $s . ".log");
//file_put_contents($log_dir . "{$user_id}.txt", $s);

$current_dir = getcwd();
do {
    if (substr($target_path, -3) !== "zip") {
        $api = createAPIObject(false, "확장명인 zip인 압축파일만 지원됩니다.");
        break;
    }
    if (!@move_uploaded_file($upload['tmp_name'], $target_path) || !file_exists($target_path)) {
        $api = createAPIObject(false, "파일 업로드를 실패했습니다.");
        break;
    }
    if (($zip = new ZipArchive())->open($target_path) !== true) {
        $api = createAPIObject(false, "유효하지 않은 압축 파일입니다.");
        break;
    }

    //exec("unzip {$name}", $log);
    if (!$zip->extractTo($target_dir)) {
        $api = createAPIObject(false, "Invalid Zip File.");
        break;
    }

    WorldProviderManager::init();
    GeneratorManager::registerDefaultGenerators();
    define('pocketmine\RESOURCE_PATH', realpath('../pmmp/resources/'));

    $oldProviderClasses = WorldProviderManager::getMatchingProviders($target_dir);
    if (count($oldProviderClasses) !== 1) {
        $api = createAPIObject(false, "올바르지 않은 맵형식입니다.");
        break;
    }

    try {
        unlink($target_path);
        $oldProviderClass = array_shift($oldProviderClasses);
        /** @var WorldProvider $oldProvider */
        $oldProvider = new $oldProviderClass($target_dir);
        if ($oldProviderClass === LevelDB::class) {
            $api = createAPIObject(false, "이미 LevelDB인 맵입니다.");
            break;
        }

        @mkdir($bp = "../worlds/bef/{$s}", 0777, true);
        $converter = new FormatConverter($oldProvider, LevelDB::class, realpath($bp), GlobalLogger::get());
        $converter->execute();
    } catch (Throwable $e) {
        $api = createAPIObject(false, "변환 중 오류가 발생하였습니다.\n{$e->getMessage()}");
        break;
    }

    try{
        chdir($target_dir);
        $log = [];
        exec("zip {$name} -r ./*", $log);
        chdir($current_dir);

        $api = createAPIObject(true, "변환을 성공하였습니다.", "{$target_dir}/{$name}");
    }catch (Throwable $e) {
        $api = createAPIObject(false, "저장 중 오류가 발생하였습니다.\n{$e->getMessage()}");
        break;
    }
} while (false);

echo json_encode(array_merge($api, ['name' => $target_dir, 'log' => LOG_DIR, 'uid' => $user_id]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);