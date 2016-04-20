<?php
    chdir(dirname(__FILE__));
    header('content-type: image/png');
    isset($_GET['steamid']) && preg_match('/\d{17}/', $_GET['steamid']) || GoDie('请输入正确的steamid');
    // bool.是否使用缓存
    $cache = true;
    // integer.获取steamid
    $steamSteamid = $_GET['steamid'];
    // string.https://steamcommunity.com/dev/apikey
    $steamAPIKey = '';
    /**
     * 获取玩家信息
     * 
     * [gameextrainfo]  string.游戏名
     * [gameid]         integer.游戏appid
     * [loccountrycode] string.国家代码
     * avatarmedium     string.头像64*64
     * personaname      string.昵称
     * personastate     integer.状态
     * profileurl       string.个人主页
     * * 0.离线
     * * 1.在线
     * * 2.忙碌
     * * 3.离开
     * * 4.打盹
     * * 5.想交易
     * * 6.想玩游戏
     */
    $playerSummaries = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.$steamAPIKey.'&steamids='.$steamSteamid.'&format=json';
    $jsonSummaries = file_get_contents($playerSummaries);
    if ($jsonSummaries)
    {
        $arraySummaries = json_decode($jsonSummaries, true)['response']['players'][0];
    }
    else
    {
        GoDie('玩家信息拉取失败');
    }
    /**
     * 获取等级,经验值
     * 
     * player_level                     integer.等级
     * player_xp                        integer.经验值
     * player_xp_needed_current_level   integer.当前等级经验
     * player_xp_needed_to_level_up     integer.升级需要经验
     */
    $playerBadges = 'https://api.steampowered.com/IPlayerService/GetBadges/v0001/?key='.$steamAPIKey.'&steamid='.$steamSteamid.'&format=json';
    $jsonBadges = file_get_contents($playerBadges);
    if ($jsonBadges)
    {
        $arrayBadges = json_decode($jsonBadges, true)['response'];
        $arrayLevel = array(
            'level' => $arrayBadges['player_level'],
            //integer.经验百分百
            'xp' => intval(360 * $arrayBadges['player_xp_needed_to_level_up'] / ($arrayBadges['player_xp'] - $arrayBadges['player_xp_needed_current_level'] + $arrayBadges['player_xp_needed_to_level_up']) - 90)
        );
    }
    else
    {
        GoDie('等级信息拉取失败');
    }
    // 缓存读取
    if ($cache)
    {
        // 需要缓存对比的信息
        $arrayCache = array(
            'gameid' => isset($arraySummaries['gameid']) ? $arraySummaries['gameid'] : 0,
            'personastate' => $arraySummaries['personastate'],
            'player_xp' => $arrayBadges['player_xp'],
            'time' => time()
        );
        // 读取缓存文件
        $arrayCacheFile = is_file('signature/'.$steamSteamid.'.json') ? json_decode(file_get_contents('signature/'.$steamSteamid.'.json'), true) : array('gameid' => 1, 'personastate' => 0, 'player_xp' => 0, 'time' => 0);
        if ($arrayCacheFile['gameid'] == $arrayCache['gameid'] && $arrayCacheFile['personastate'] == $arrayCache['personastate'] && $arrayCacheFile['player_xp'] == $arrayCache['player_xp'] )
        {
            header('last-modified: '.gmdate('D, d M Y H:i:s', $arrayCacheFile['time']).' GMT');
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $arrayCacheFile['time'])
            {
                header('status: 304 Not Modified');
            }
            else
            {
                $signature = imagecreatefrompng('signature/'.$steamSteamid.'.png');
                imagesavealpha($signature, true);
                imagepng($signature);
            }
            exit();
        }
    }
    
    $signature = Generate();
    // 缓存写入
    if ($cache)
    {
        header('last-modified: '.gmdate('D, d M Y H:i:s', $arrayCache['time']).' GMT');
        file_put_contents('signature/'.$steamSteamid.'.json', json_encode($arrayCache));
        imagepng($signature, 'signature/'.$steamSteamid.'.png');
    }
    // 输出图像退出
    imagepng($signature);
    exit();
    /**
     * 生成图像
     * 
     * @global array $arrayLevel
     * @global array $arraySummaries
     * @param string $error
     * @return resource
     */
    function Generate ($error = '')
    {
        global $arrayLevel, $arraySummaries;
        
        $signature = imagecreatefrompng('image/backgroundAvatar.png');
        imagesavealpha($signature, true);
        
        $levelBluePurple = 'image/levelBluePurple.png';
        $levelWhite = 'image/levelWhite.png';
        $maskError = 'image/maskError.png';
        $maskIngame = 'image/maskIngame.png';
        $maskOffline = 'image/maskOffline.png';
        $maskOnline = 'image/maskOnline.png';
        
        $fontBold = 'font/NotoSansSC-Bold.otf';
        $fontRegular = 'font/NotoSansSC-Regular.otf';
        
        $colorBlack = imagecolorallocatealpha($signature, 0, 0, 0, 127);
        $colorWhite = imagecolorallocate($signature, 255, 255, 255);
        // 错误
        if ($error)
        {
            $backgroundGame = 'https://steamcdn-a.akamaihd.net/steamcommunity/public/images/apps/753/ebc73b4e326945ea7eb986d93e2b1aabb291fe7d.jpg';
            $imageMask = imagecreatefrompng($maskError);
            // 背景图片
            $imageGame = imagecreatefromjpeg($backgroundGame);
            imagecopy($signature, $imageGame, 216, 6, 0, 0, 184, 69);
            // 遮罩
            imagecopy($signature, $imageMask, 0, 0, 0, 0, 400, 75);
            // 状态
            imagettftext($signature, 13, 0, 130, 45, $colorWhite, $fontRegular, $error);
            
            return $signature;
        }
        // 抓取个人主页
        $profile = file_get_contents($arraySummaries['profileurl']);
        // 最近游戏
        if (isset($arraySummaries['gameid']))
        {
            $backgroundGame = 'https://steamcdn-a.akamaihd.net/steam/apps/'.$arraySummaries['gameid'].'/capsule_184x69.jpg';
        }
        else if (preg_match('/game_info_cap[^\d]+(\d+)/', $profile, $recentGame))
        {
            $backgroundGame = 'https://steamcdn-a.akamaihd.net/steam/apps/'.$recentGame[1].'/capsule_184x69.jpg';
        }
        else
        {
            $backgroundGame = 'https://steamcdn-a.akamaihd.net/steamcommunity/public/images/apps/753/ebc73b4e326945ea7eb986d93e2b1aabb291fe7d.jpg';
        }
        isset($arraySummaries['loccountrycode']) && $countryflags = 'https://steamcommunity-a.akamaihd.net/public/images/countryflags/'.strtolower($arraySummaries['loccountrycode']).'.gif';
        // 离线
        if (0 == $arraySummaries['personastate'])
        {
            $imageMask = imagecreatefrompng($maskOffline);
            $state = '离线';
        }
        // 游戏
        else if(isset($arraySummaries['gameid']))
        {
            $imageMask = imagecreatefrompng($maskIngame);
            $state = isset($arraySummaries['gameextrainfo']) ? '正在游戏' : '正在挂卡';
        }
        // 在线
        else
        {
            $imageMask = imagecreatefrompng($maskOnline);
            switch ($arraySummaries['personastate'])
            {
                case 1:
                    $state = '在线';
                    break;
                case 2:
                    $state = '忙碌';
                    break;
                case 3:
                    $state = '离开';
                    break;
                case 4:
                    $state = '打盹';
                    break;
                case 5:
                    $state = '想交易';
                    break;
                case 6:
                    $state = '想玩游戏';
            }
        }
        // 背景图片
        $imageGame = imagecreatefromjpeg($backgroundGame);
        imagecopy($signature, $imageGame, 216, 6, 0, 0, 184, 69);
        // 遮罩
        imagecopy($signature, $imageMask, 0, 0, 0, 0, 400, 75);
        // 状态
        imagettftext($signature, 13, 0, 130, 45, $colorWhite, $fontRegular, $state);
        // 头像
        if (isset($arraySummaries['avatarmedium']))
        {
            $imageAvatar = imagecreatefromjpeg($arraySummaries['avatarmedium']);
            imagecopy($signature, $imageAvatar, 0, 0, 0, 0, 64, 64);
        }
        // 国旗
        if (isset($countryflags))
        {
            $imageFlags = imagecreatefromgif($countryflags);
            imagecopy($signature, $imageFlags, 64, 53, 0, 0, 16, 11);
        }
        // 徽章
        if (preg_match('/src="([^"]+)">\s*?<\/a>\s*?<\/div>\s*?<div class="favorite_badge_description">/', $profile, $favoriteBadge))
        {
            $favoriteBadge[1] = str_replace('_54', '_80', $favoriteBadge[1]);
            $imageBadge = imagecreatefrompng($favoriteBadge[1]);
            imagecopyresampled($signature, $imageBadge, 98, 34, 0, 0, 30, 30, 80, 80);
        }
        // 昵称
        if (isset($arraySummaries['personaname']))
        {
            imagettftext($signature, 12, 0, 130, 25, $colorWhite, $fontBold, $arraySummaries['personaname']);
        }
        // 游戏名
        if (isset($arraySummaries['gameextrainfo']))
        {
            imagettftext($signature, 10, 0, 130, 70, $colorWhite, $fontRegular, $arraySummaries['gameextrainfo']);
        }
        // 等级
        if (isset($arrayLevel['level']))
        {
            // 外部白圈
            $imageWhite = imagecreatefrompng($levelWhite);
            imagecopy($signature, $imageWhite, 64, 0, 0, 0, 40, 40);
            // 内部蓝紫圈
            $imageBluePurple = imagecreatefrompng($levelBluePurple);
            //填充颜色使用替换模式
            imagealphablending($imageBluePurple, false);
            //剩余经验百分比填充为透明
            imagefilledarc($imageBluePurple, 20, 20 , 40, 40, -90, $arrayLevel['xp'], $colorBlack, IMG_ARC_EDGED);
            imagecopy($signature, $imageBluePurple, 64, 0, 0, 0, 40, 40);
            // 调整等级文字位置
            switch (strlen($arrayLevel['level']))
            {
                case 1:
                    $size = 13;
                    $x = 79;
                    $y = 27;
                    break;
                case 2:
                    $size = 13;
                    $x = 74;
                    $y = 27;
                    break;
                case 3:
                    $size = 13;
                    $x = 69;
                    $y = 27;
                    break;
                case 4:
                    $size = 10;
                    $x = 68;
                    $y = 25;
            }
            imagettftext($signature, $size, 0, $x, $y, $colorWhite, $fontBold, $arrayLevel['level']);
        }
        
        return $signature;
    }
    /**
     * 输出错误信息
     * 
     * @param string $error
     */
    function GoDie ($error)
    {
        $signature = Generate($error);
        imagepng($signature);
        exit();
    }
?>