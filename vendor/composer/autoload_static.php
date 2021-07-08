<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbfb788e766707f5b05e59cce0d57a3fa
{
    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'ErrorCode' => __DIR__ . '/../..' . '/toolkit/examples/webrpc/updateleaderboard.php',
        'ErrorCodes' => __DIR__ . '/../..' . '/toolkit/examples/getleaderboard.php',
        'GetLeaderboardRequest' => __DIR__ . '/../..' . '/toolkit/examples/getleaderboard.php',
        'GetLeaderboardResponse' => __DIR__ . '/../..' . '/toolkit/examples/getleaderboard.php',
        'ImpossibleOdds\\Photon\\WebRpc\\ParsingContext' => __DIR__ . '/../..' . '/toolkit/photon/webrpc/parsingcontext.php',
        'ImpossibleOdds\\Photon\\WebRpc\\WebRpcRequest' => __DIR__ . '/../..' . '/toolkit/photon/webrpc/webrpcrequest.php',
        'ImpossibleOdds\\Photon\\WebRpc\\WebRpcResponse' => __DIR__ . '/../..' . '/toolkit/photon/webrpc/webrpcresponse.php',
        'ImpossibleOdds\\Photon\\WebRpc\\WebRpcResponseData' => __DIR__ . '/../..' . '/toolkit/photon/webrpc/webrpcresponsedata.php',
        'ImpossibleOdds\\Serialization\\IAnnotationsCache' => __DIR__ . '/../..' . '/toolkit/serialization/iannotationscache.php',
        'ImpossibleOdds\\Serialization\\LocalAnnotationsCache' => __DIR__ . '/../..' . '/toolkit/serialization/localannotationscache.php',
        'ImpossibleOdds\\Serialization\\RequiredPropertyException' => __DIR__ . '/../..' . '/toolkit/serialization/requiredpropertyexception.php',
        'ImpossibleOdds\\Serialization\\Serializer' => __DIR__ . '/../..' . '/toolkit/serialization/serializer.php',
        'ImpossibleOdds\\Serialization\\SubClassDefinition' => __DIR__ . '/../..' . '/toolkit/serialization/serializer.php',
        'ImpossibleOdds\\Serialization\\SupportedAnnotationTags' => __DIR__ . '/../..' . '/toolkit/serialization/serializer.php',
        'ImpossibleOdds\\WebRequests\\ParsingContext' => __DIR__ . '/../..' . '/toolkit/unity/parsingcontext.php',
        'ImpossibleOdds\\WebRequests\\UnityWebRequest' => __DIR__ . '/../..' . '/toolkit/unity/unitywebrequest.php',
        'ImpossibleOdds\\WebRequests\\UnityWebRequestMode' => __DIR__ . '/../..' . '/toolkit/unity/requestmodes.php',
        'ImpossibleOdds\\WebRequests\\UnityWebResponse' => __DIR__ . '/../..' . '/toolkit/unity/unitywebresponse.php',
        'Leaderboard' => __DIR__ . '/../..' . '/toolkit/examples/getleaderboard.php',
        'LeaderboardEntry' => __DIR__ . '/../..' . '/toolkit/examples/getleaderboard.php',
        'UpdateLeaderboardCodes' => __DIR__ . '/../..' . '/toolkit/examples/webrpc/updateleaderboard.php',
        'UpdateLeaderboardRequest' => __DIR__ . '/../..' . '/toolkit/examples/webrpc/updateleaderboard.php',
        'UpdateLeaderboardResponseData' => __DIR__ . '/../..' . '/toolkit/examples/webrpc/updateleaderboard.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInitbfb788e766707f5b05e59cce0d57a3fa::$classMap;

        }, null, ClassLoader::class);
    }
}
