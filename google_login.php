<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/google_config.php';


/*
|--------------------------------------------------------------------------
| JSON Response
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=utf-8'
);


function google_json(
    $success,
    $message,
    $redirect = 'index.php'
) {

    echo json_encode(
        [
            'ok' => $success,
            'message' => $message,
            'redirect' => $redirect
        ]
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Only POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    google_json(
        false,
        'Invalid request method.'
    );

}


/*
|--------------------------------------------------------------------------
| CSRF Check
|--------------------------------------------------------------------------
*/

$sessionCsrf =
    $_SESSION['csrf_token'] ?? '';

$postCsrf =
    $_POST['csrf_token'] ?? '';


if (
    empty($sessionCsrf) ||
    empty($postCsrf) ||
    !hash_equals(
        $sessionCsrf,
        $postCsrf
    )
) {

    google_json(
        false,
        'Security check failed. Please refresh the page and try again.'
    );

}


/*
|--------------------------------------------------------------------------
| Google Credential
|--------------------------------------------------------------------------
*/

$credential =
    trim(
        $_POST['credential'] ?? ''
    );


if ($credential === '') {

    google_json(
        false,
        'Google sign-in was not completed.'
    );

}


/*
|--------------------------------------------------------------------------
| Check Google Client ID
|--------------------------------------------------------------------------
*/

if (
    !defined('GOOGLE_CLIENT_ID') ||
    GOOGLE_CLIENT_ID === '' ||
    strpos(
        GOOGLE_CLIENT_ID,
        'PASTE_YOUR'
    ) === 0
) {

    google_json(
        false,
        'Google Login is not configured yet.'
    );

}


/*
|--------------------------------------------------------------------------
| Verify Google ID Token
|--------------------------------------------------------------------------
|
| Google provides tokeninfo endpoint for ID token validation.
|
*/

$googleUrl =
    'https://oauth2.googleapis.com/tokeninfo?id_token=' .
    urlencode($credential);


$ch =
    curl_init(
        $googleUrl
    );


curl_setopt_array(
    $ch,
    [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_TIMEOUT => 15,

        CURLOPT_CONNECTTIMEOUT => 5,

        CURLOPT_SSL_VERIFYPEER => true,

        CURLOPT_HTTPHEADER => [
            'Accept: application/json'
        ]

    ]
);


$response =
    curl_exec($ch);


$curlError =
    curl_error($ch);


$httpCode =
    (int) curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);


/*
|--------------------------------------------------------------------------
| Google Request Error
|--------------------------------------------------------------------------
*/

if (
    $response === false ||
    $curlError ||
    $httpCode !== 200
) {

    error_log(
        'Google token verification failed: ' .
        (
            $curlError !== ''
                ? $curlError
                : 'HTTP ' . $httpCode
        )
    );


    google_json(
        false,
        'Google verification failed. Please try again.'
    );

}


/*
|--------------------------------------------------------------------------
| Decode Google Response
|--------------------------------------------------------------------------
*/

$claims =
    json_decode(
        $response,
        true
    );


if (!is_array($claims)) {

    google_json(
        false,
        'Invalid response received from Google.'
    );

}


/*
|--------------------------------------------------------------------------
| Extract Google Claims
|--------------------------------------------------------------------------
*/

$issuer =
    trim(
        $claims['iss'] ?? ''
    );


$audience =
    trim(
        $claims['aud'] ?? ''
    );


$googleId =
    trim(
        $claims['sub'] ?? ''
    );


$email =
    strtolower(
        trim(
            $claims['email'] ?? ''
        )
    );


$name =
    trim(
        $claims['name'] ?? ''
    );


$expires =
    isset($claims['exp'])
        ? (int) $claims['exp']
        : 0;


$emailVerified =
    filter_var(
        $claims['email_verified'] ?? false,
        FILTER_VALIDATE_BOOLEAN
    );


/*
|--------------------------------------------------------------------------
| Validate Google Claims
|--------------------------------------------------------------------------
*/

$validIssuer =
    in_array(
        $issuer,
        [
            'accounts.google.com',
            'https://accounts.google.com'
        ],
        true
    );


$validAudience =
    hash_equals(
        GOOGLE_CLIENT_ID,
        $audience
    );


$validExpiry =
    $expires > time();


$validEmail =
    filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    );


if (
    !$validIssuer ||
    !$validAudience ||
    !$validExpiry ||
    !$validEmail ||
    !$emailVerified ||
    $googleId === ''
) {

    google_json(
        false,
        'Google account verification failed.'
    );

}


/*
|--------------------------------------------------------------------------
| Fallback Name
|--------------------------------------------------------------------------
*/

if ($name === '') {

    $name =
        strstr(
            $email,
            '@',
            true
        );

}


/*
|--------------------------------------------------------------------------
| Find User By Google ID
|--------------------------------------------------------------------------
*/

$stmt =
    $conn->prepare(
        "
        SELECT *
        FROM users
        WHERE google_id = ?
        LIMIT 1
        "
    );


if (!$stmt) {

    error_log(
        'Google user lookup prepare failed: ' .
        $conn->error
    );


    google_json(
        false,
        'Database error.'
    );

}


$stmt->bind_param(
    's',
    $googleId
);


$stmt->execute();


$result =
    $stmt->get_result();


$user =
    $result->fetch_assoc();


$stmt->close();


/*
|--------------------------------------------------------------------------
| If Not Found, Find User By Email
|--------------------------------------------------------------------------
*/

if (!$user) {

    $stmt =
        $conn->prepare(
            "
            SELECT *
            FROM users
            WHERE LOWER(email) = ?
            LIMIT 1
            "
        );


    if (!$stmt) {

        error_log(
            'Email lookup prepare failed: ' .
            $conn->error
        );


        google_json(
            false,
            'Database error.'
        );

    }


    $stmt->bind_param(
        's',
        $email
    );


    $stmt->execute();


    $result =
        $stmt->get_result();


    $user =
        $result->fetch_assoc();


    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| Existing User
|--------------------------------------------------------------------------
*/

if ($user) {

    /*
     * Link Google account with existing account.
     */

    $stmt =
        $conn->prepare(
            "
            UPDATE users
            SET
                google_id = ?,
                is_verified = 1
            WHERE id = ?
            "
        );


    if ($stmt) {

        $userId =
            (int) $user['id'];


        $stmt->bind_param(
            'si',
            $googleId,
            $userId
        );


        $stmt->execute();


        $stmt->close();

    }

}


/*
|--------------------------------------------------------------------------
| New User
|--------------------------------------------------------------------------
*/

else {

    /*
     * Google users do not need a normal password.
     *
     * We still generate a random password because
     * the existing users table may require this field.
     */

    $randomPassword =
        password_hash(
            bin2hex(
                random_bytes(32)
            ),
            PASSWORD_DEFAULT
        );


    $phone = '';

    $role = 'user';

    $verified = 1;


    $stmt =
        $conn->prepare(
            "
            INSERT INTO users
            (
                name,
                email,
                phone,
                password,
                role,
                is_verified,
                google_id
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
            "
        );


    if (!$stmt) {

        error_log(
            'Google user insert prepare failed: ' .
            $conn->error
        );


        google_json(
            false,
            'Could not create your account.'
        );

    }


    $stmt->bind_param(
        'sssssis',
        $name,
        $email,
        $phone,
        $randomPassword,
        $role,
        $verified,
        $googleId
    );


    if (!$stmt->execute()) {

        error_log(
            'Google user insert failed: ' .
            $stmt->error
        );


        $stmt->close();


        google_json(
            false,
            'Could not create your account.'
        );

    }


    $userId =
        $conn->insert_id;


    $stmt->close();


    $user = [

        'id' =>
            $userId,

        'name' =>
            $name,

        'email' =>
            $email,

        'role' =>
            $role

    ];

}


/*
|--------------------------------------------------------------------------
| Create Login Session
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);


$_SESSION['user_id'] =
    (int) $user['id'];


$_SESSION['name'] =
    $user['name'];


$_SESSION['email'] =
    $user['email'];


$_SESSION['role'] =
    $user['role'] ?? 'user';


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['role']) &&
    $_SESSION['role'] === 'admin'
) {

    $redirect =
        'admin/index.php';

} else {

    $redirect =
        'index.php';

}


/*
|--------------------------------------------------------------------------
| Success
|--------------------------------------------------------------------------
*/

google_json(
    true,
    'Google login successful.',
    $redirect
);