<?php

namespace App\Http\Controllers\Api;

use App\EventFilter\Facades\Filter;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\CommentMention;
use Exception;
use Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Settings;

/**
 * Class TaskCommentController
 * @deprecated
 * @codeCoverageIgnore
 *
 * @codeCoverageIgnore
 * @deprecated
 */
class TaskCommentController extends ItemController
{
    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'task-comment.list',
            'create' => 'task-comment.create',
            'show' => 'task-comment.show',
            'destroy' => 'task-comment.remove',
        ];
    }

    public function create(Request $request): JsonResponse
    {
        Filter::listen($this->getEventUniqueName('request.item.create'), static function (array $data) {
            $data['user_id'] = Auth::id();

            return $data;
        });

        Event::listen($this->getEventUniqueName('item.create.after'), static function (TaskComment $item, array $requestData) {
            if (preg_match_all('/@([0-9a-zа-я._-]+)/i', $item->content, $matches)) {
                foreach ($matches[1] as $userName) {
                    $user = User::query()->whereRaw("REPLACE(full_name, ' ', '') = ?", [$userName])->first();
                    if ($user) {
                        /** @var User $user */
                        $language = !empty($user->user_language) ? $user->user_language : Settings::get('core', 'language', 'en');
                        $user->notify((new CommentMention($item))->locale($language));
                    }
                }
            }
        });

        return $this->_create($request);
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'task_id' => 'required',
            'content' => 'required',
        ];
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /task-comment/create Create
     * @apiDescription  Create Task Comment
     *
     * @apiVersion      1.0.0
     * @apiName         CreateTaskComment
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_create
     * @apiPermission   task_comment_full_access
     */

    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return TaskComment::class;
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'taskcomment';
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /task-comment/list List
     * @apiDescription  Get list of Task Comments
     *
     * @apiVersion      1.0.0
     * @apiName         GetTaskCommentList
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_list
     * @apiPermission   task_comment_full_access
     */
    /**
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery()->with('user'),
            $filters ?: []
        );

        $user = Auth::user();
        $full_access = $user->allowed('task-comment', 'full_access');

        if (!$full_access) {
            $baseQuery->whereHas('task', static function ($taskQuery) use ($user) {
                $taskQuery->where(['user_id' => $user->id]);
            });
        }

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );

        return new JsonResponse(
            Filter::process(
                $this->getEventUniqueName('answer.success.item.list.result'),
                $itemsQuery->get()
            )
        );
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /task-comment/show Show
     * @apiDescription  Show Task Comment
     *
     * @apiVersion      1.0.0
     * @apiName         ShowTaskComment
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_show
     * @apiPermission   task_comment_full_access
     */
    public function show(Request $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /task-comment/remove Destroy
     * @apiDescription  Destroy Task Comment
     *
     * @apiVersion      1.0.0
     * @apiName         DestroyTaskComment
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_remove
     * @apiPermission   task_comment_full_access
     */
    /**
     * @throws Exception
     */
    public function destroy(Request $request): JsonResponse
    {
        $itemId = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->get('id'));
        $idInt = is_int($itemId);

        if (!$idInt) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.destroy'), [
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'Invalid id',
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(),
                ['id' => $itemId]
            )
        );

        $user = Auth::user();
        $full_access = $user->allowed('task-comment', 'full_access');

        if (!$full_access) {
            $itemsQuery->where(['user_id' => $user->id])
                ->whereHas('task', static function ($taskQuery) use ($user) {
                    $taskQuery->where(['user_id' => $user->id]);
                });
        }

        /** @var Model $item */
        $item = $itemsQuery->firstOrFail();
        $item->delete();

        return new JsonResponse(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'Item has been removed'
            ])
        );
    }
}
