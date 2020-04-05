<?php 
  
    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Contracts\Auth\Factory as Auth;

    class CorsMiddleware
    {
        /**
         * The authentication guard factory instance.
         *
         * @var \Illuminate\Contracts\Auth\Factory
         */
        protected $auth;

        /**
         * Create a new middleware instance.
         *
         * @param  \Illuminate\Contracts\Auth\Factory  $auth
         * @return void
         */
        public function __construct(Auth $auth)
        {
            $this->auth = $auth;
        }

        /**
        * Handle an incoming request.
        *
        * @param  \Illuminate\Http\Request  $request
        * @param  \Closure  $next
        * @return mixed
        */

        public function handle($request, Closure $next)
        {
            $headers = [
                'Access-Control-Allow-Origin'      => '*',
                'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age'           => '86400',
                'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
            ];

            if ($request->isMethod('OPTIONS') || $request->getMethod() == "OPTIONS")
            {
               return response()->json('{"method":"OPTIONS"}', 200, $headers);
            }

            // if ($this->auth->guard($guard)->guest()) {
                    
            //     return response()->json(['message'=>'Unauthorized'], 401);
            // }

            $response = $next($request);
            foreach($headers as $key => $value)
            {
                $response->header($key, $value);
            }
    
            return $response;
            

        }
    }