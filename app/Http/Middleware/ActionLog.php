<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ActionLog as ActionLogModel;

class ActionLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $this->actionLog($request, $response->status());

        return $response;
    }

    public function actionLog($request, $status)
    {
        $user = $request->user();
        $data = [
            'staff_id' => $user ? $user->id : null,
            'url' => $request->path(),
            'method' => $request->method(),
            'status' => $status,
            'message' => $this->getMessage($request),
            'remote_addr' => $request->getClientIps()[0],
            'user_agent' => $request->userAgent(),
            'created_at' => microtime(true),
        ];
        ActionLogModel::create($data);
    }

    private function getMessage($request)
    {
        $message = $request->toArray();
        if (count($message) == 0) {
            return null;
        }
        $message = $this->replacePassword($message, $request);
        return json_encode($message, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    }

    private function replacePassword($message, $request)
    {
        if ($request->isMethod('post')) {
            $replace = config('const.passwordKeys')['replace'];
            $path = preg_replace('/\/[0-9]+$/u', '', $request->path());
            if (!empty($replace[$path])) {
                foreach ($replace[$path] as $key) {
                    if (isset($message[$key])) {
                        $message[$key] = bcrypt($message[$key]);
                    }
                }
            }
        }
        return $message;
    }
}
