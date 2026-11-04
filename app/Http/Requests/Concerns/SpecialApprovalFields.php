<?php

namespace App\Http\Requests\Concerns;

trait SpecialApprovalFields
{
    public const MAX_DOCUMENTS = 5;

    public const MAX_DOCUMENT_KB = 5120;

    public const DOCUMENT_MIMES = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

    protected function prepareSpecialApproval(): void
    {
        $this->merge(['special_approval' => $this->boolean('special_approval')]);
    }

    /**
     * @return array<string, list<string>>
     */
    protected function specialApprovalRules(): array
    {
        return [
            'special_approval' => ['boolean'],
            'special_documents' => ['nullable', 'array', 'max:'.self::MAX_DOCUMENTS],
            'special_documents.*' => ['file', 'mimes:'.implode(',', self::DOCUMENT_MIMES), 'max:'.self::MAX_DOCUMENT_KB],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function specialApprovalAttributes(): array
    {
        return [
            'special_approval' => 'special approval',
            'special_documents' => 'supporting documents',
            'special_documents.*' => 'supporting document',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function specialApprovalMessages(): array
    {
        return [
            'special_documents.max' => 'Attach up to '.self::MAX_DOCUMENTS.' supporting documents.',
            'special_documents.*.mimes' => 'Supporting documents must be PDF, Word, JPG, or PNG files.',
            'special_documents.*.max' => 'Each supporting document must be 5 MB or smaller.',
        ];
    }
}
