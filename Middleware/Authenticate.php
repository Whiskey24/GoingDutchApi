<?php


namespace Middleware;
use Db;

class Authenticate
{
    /**
     * Authenticate middleware invokable class
     *
     * Uses basic authorization field: https://en.wikipedia.org/wiki/Basic_access_authentication#Client_side
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface $response PSR7 response
     * @param  callable $next Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */

    public static $requestUid;

    public function __invoke($request, $response, $next)
    {
        $authorized = false;

        if ($request->hasHeader('PHP_AUTH_USER')) {
            $temp = base64_encode("whiskey:testpassword");
            // get credentials from header and salt from config
            $salt = 's8w4Er97u';
            $name = $request->getHeader('PHP_AUTH_USER')[0];
            $pass = md5($salt . $request->getHeader('PHP_AUTH_PW')[0] . $salt);

            // validate credentials
            /* "SELECT users.*, name_format, email_notify FROM {$this->opt['table_name']}
              LEFT JOIN preferences ON users.user_id = preferences.user_id WHERE username='{$this->username}' AND password='{$this->pass}'";*/
            $stmt = Db::getInstance()->prepare("SELECT users.* FROM users WHERE username = :name AND password = :pass");
            $stmt->execute(array(':name' => $name, ':pass' => $pass));
            $result = $stmt->fetch();
            if ($result) {
                $authorized = true;
                self::$requestUid = intval($result['user_id']);
            }
        }

        if (!$authorized)
            return $response->withStatus(403)->write('Not authorized');

        $response = $next($request, $response);
        return $response;
    }

}