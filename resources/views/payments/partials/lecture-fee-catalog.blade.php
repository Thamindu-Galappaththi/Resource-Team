@php
    $typeLabels = $types ?? config('lecture-fees.types');
    $courseLabels = $courseLevels ?? config('lecture-fees.course_levels');
    $qualificationLabels = $qualifications ?? config('lecture-fees.qualifications');
@endphp
<div class="table-responsive">
    <table class="table rsv-table mb-0" id="lf-table">
        <thead>
            <tr>
                <th>Lecture type</th>
                <th>Course level</th>
                <th>Qualification</th>
                <th>Hourly rate</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rules as $rule)
            @php
                $typeLabel = $typeLabels[$rule->user_type] ?? $rule->user_type;
                $courseLabel = $courseLabels[$rule->department] ?? $rule->department;
                $qualificationLabel = $qualificationLabels[$rule->qualification] ?? $rule->qualification;
                $hourly = (float) $rule->hourly_rate_minor / 100;
            @endphp
            <tr>
                <td data-label="Lecture type"><strong>{{ $typeLabel }}</strong></td>
                <td data-label="Course level">{{ $courseLabel }}</td>
                <td data-label="Qualification">{{ $qualificationLabel }}</td>
                <td data-label="Hourly rate" class="rr-money"><strong>LKR {{ number_format($hourly, 2) }}</strong></td>
                <td data-label="Status">
                    @if($rule->is_active)
                        <span class="badge text-bg-success">Active</span>
                    @else
                        <span class="badge text-bg-secondary">Inactive</span>
                    @endif
                </td>
                <td data-label="Actions" class="rsv-actions">
                    <div class="rr-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary rsv-icon-btn lf-edit"
                            title="Edit" aria-label="Edit {{ $typeLabel }}"
                            data-action="{{ route('payments.lecture-fees.update', $rule) }}"
                            data-label="{{ $typeLabel }}"
                            data-type="{{ $rule->user_type }}"
                            data-type-label="{{ $typeLabel }}"
                            data-course="{{ $rule->department }}"
                            data-course-label="{{ $courseLabel }}"
                            data-qualification="{{ $rule->qualification }}"
                            data-qualification-label="{{ $qualificationLabel }}"
                            data-rate="{{ number_format($hourly, 2, '.', '') }}"
                            data-status="{{ $rule->is_active ? 'active' : 'inactive' }}"><i class="ti ti-edit"></i></button>
                        @if($rule->is_active)
                            <form method="POST" action="{{ route('payments.lecture-fees.status', $rule) }}" data-confirm-title="Deactivate this rate?" data-confirm-text="This hourly price will not be used for new lecture payments." data-confirm-button="Deactivate" data-confirm-kind="deactivate">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="inactive">
                                <button class="btn btn-sm btn-outline-danger rsv-icon-btn" type="submit" title="Deactivate" aria-label="Deactivate {{ $typeLabel }}"><i class="ti ti-ban"></i></button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('payments.lecture-fees.status', $rule) }}" data-confirm-title="Activate this rate?" data-confirm-text="This hourly price will be used for Human Resource lectures." data-confirm-button="Activate" data-confirm-kind="activate">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="active">
                                <button class="btn btn-sm btn-outline-success rsv-icon-btn" type="submit" title="Activate" aria-label="Activate {{ $typeLabel }}"><i class="ti ti-circle-check"></i></button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('payments.lecture-fees.destroy', $rule) }}" data-action-lock="off" data-confirm-title="Delete this rate?" data-confirm-text="This hourly price will be removed from the lecture rate catalog." data-confirm-button="Delete" data-confirm-kind="delete">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger rsv-icon-btn" type="submit" title="Delete" aria-label="Delete {{ $typeLabel }}"><i class="ti ti-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr class="rr-empty-row"><td colspan="6">{{ $totalCount ? 'No lecture rates match those filters.' : 'No lecture rates have been assigned.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('payments.partials.history-pager', ['paginator' => $rules, 'section' => 'lecture_fees'])
