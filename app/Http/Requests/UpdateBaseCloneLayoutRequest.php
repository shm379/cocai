<?php

namespace App\Http\Requests;

use App\Models\BaseClone;
use App\Services\BaseClone\LayoutEditValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * درخواست ویرایش دستی چیدمان بیس (فقط مالک).
 *
 * اعتبارسنجی شکلی اینجا انجام می‌شود؛ قوانین هندسی (هم‌پوشانی، مرز شبکه، نوع کاتالوگ)
 * در LayoutEditValidator بررسی می‌شوند.
 */
class UpdateBaseCloneLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $clone = $this->route('clone');
        $user = $this->user();

        return $clone instanceof BaseClone && $user !== null && (int) $clone->user_id === (int) $user->id;
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'ok' => false,
            'message' => 'شما اجازهٔ ویرایش این بیس را ندارید.',
        ], 403));
    }

    public function rules(): array
    {
        $maxCoord = 127;

        return [
            'version' => ['required', 'integer', 'min:1'],
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'buildings' => ['present', 'array', 'max:'.LayoutEditValidator::MAX_BUILDINGS],
            'buildings.*' => ['array'],
            'buildings.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'buildings.*.type' => ['required', 'string', 'max:64'],
            'buildings.*.x' => ['required', 'integer', 'min:0', 'max:'.$maxCoord],
            'buildings.*.y' => ['required', 'integer', 'min:0', 'max:'.$maxCoord],
            'buildings.*.level' => ['nullable', 'integer', 'min:1', 'max:50'],
            'buildings.*.placed' => ['sometimes', 'boolean'],
            'buildings.*.user_fixed' => ['sometimes', 'boolean'],
            'buildings.*.verified' => ['sometimes', 'boolean'],
            'walls' => ['present', 'array', 'max:'.LayoutEditValidator::MAX_WALLS],
            'walls.*' => ['array', 'size:2'],
            'walls.*.*' => ['integer', 'min:0', 'max:'.$maxCoord],
        ];
    }

    public function messages(): array
    {
        return [
            'version.required' => 'نسخهٔ چیدمان ارسال نشده است.',
            'version.integer' => 'نسخهٔ چیدمان باید عدد باشد.',
            'buildings.present' => 'فهرست ساختمان‌ها ارسال نشده است.',
            'buildings.max' => 'حداکثر '.LayoutEditValidator::MAX_BUILDINGS.' ساختمان مجاز است.',
            'buildings.*.id.required' => 'شناسهٔ ساختمان الزامی است.',
            'buildings.*.id.distinct' => 'شناسهٔ ساختمان تکراری است.',
            'buildings.*.type.required' => 'نوع ساختمان الزامی است.',
            'buildings.*.x.required' => 'مختصات x الزامی است.',
            'buildings.*.y.required' => 'مختصات y الزامی است.',
            'buildings.*.x.integer' => 'مختصات x باید عدد صحیح باشد.',
            'buildings.*.y.integer' => 'مختصات y باید عدد صحیح باشد.',
            'buildings.*.level.integer' => 'سطح ساختمان باید عدد صحیح باشد.',
            'walls.present' => 'فهرست دیوارها ارسال نشده است.',
            'walls.max' => 'حداکثر '.LayoutEditValidator::MAX_WALLS.' قطعه دیوار مجاز است.',
            'walls.*.size' => 'هر دیوار باید به شکل [x, y] باشد.',
            'walls.*.*.integer' => 'مختصات دیوار باید عدد صحیح باشد.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'ok' => false,
            'message' => 'اطلاعات ارسالی نامعتبر است.',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
