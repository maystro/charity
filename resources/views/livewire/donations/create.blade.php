@php
use App\Enums\DonorType;
@endphp

<div class="space-y-6">
    <x-layout.page-header
        title="إضافة تبرع جديد"
        subtitle="سجّل تبرعًا جديدًا بالقيم الظاهرة في سجل التبرعات"
        :breadcrumbs="[['label' => 'التبرعات', 'route' => 'donations.index'], ['label' => 'إضافة تبرع']]"
    />

    <form wire:submit="save" class="space-y-6">
        <x-ui.card padding>
            <div class="space-y-4">
                <div>
                    <h3 class="text-base font-semibold text-[var(--color-text-primary)]">بيانات التبرع</h3>
                    <p class="text-xs text-[var(--color-text-muted)] mt-0.5">اختيار متبرع مسجّل يملأ النوع تلقائيًا. يمكنك إضافة متبرع جديد بالضغط على زر (+).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- المتبرع المسجل + زر الإضافة --}}
                    <div>
                        <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">المتبرع المسجل</label>
                        <div class="flex">
                            <div class="flex-1">
                                <x-ui.combobox
                                    label=""
                                    name="donor_id"
                                    wire:model.live="donor_id"
                                    :options="$donors"
                                    placeholder="اختر متبرعًا مسجلًا..."
                                    searchPlaceholder="ابحث باسم المتبرع أو الجهة..."
                                    emptyText="لا يوجد متبرعون مطابقون"
                                    :selected="$donor_id"
                                />
                            </div>
                            <x-ui.button type="button" variant="ghost" wire:click="openNewDonorModal" title="إضافة متبرع جديد" icon="plus" class="mr-[5px] shrink-0 !gap-0 !px-0 w-[var(--control-height)]" />
                        </div>
                    </div>

                    {{-- نوع المتبرع (يُملأ تلقائيًا عند اختيار متبرع مسجل) --}}
                    <div>
                        <x-ui.select label="نوع المتبرع" name="donor_type" wire:model="donor_type">
                            @foreach($donorTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div>
                        <x-ui.input
                            label="قيمة التبرع"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            wire:model="amount"
                            required
                        />
                    </div>

                    <div>
                        <x-ui.select label="وسيلة التبرع" name="method" wire:model="method" required>
                            @foreach($methodOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div>
                        <x-ui.select label="نوع التبرع" name="type" wire:model="type" required>
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div>
                        <x-ui.select label="المشروع المرتبط" name="project_id" wire:model="project_id" :placeholder="null">
                            <option value="">تبرع عام</option>
                            @foreach($projects as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    <div>
                        <x-ui.date-input label="تاريخ التبرع" name="donated_at" wire:model="donated_at" required />
                    </div>

                    <div class="md:col-span-2">
                        <x-ui.textarea
                            label="ملاحظات"
                            name="notes"
                            rows="4"
                            placeholder="أي معلومات إضافية تخص التبرع..."
                            wire:model="notes"
                        />
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button variant="ghost" href="{{ route('donations.index') }}" wire:navigate>إلغاء</x-ui.button>
            <x-ui.button variant="primary" icon="check" type="submit" wire:loading.attr="disabled">حفظ التبرع</x-ui.button>
        </div>
    </form>

    {{-- Modal: إضافة متبرع جديد --}}
    @if($showNewDonorModal)
        <x-ui.modal name="new-donor" title="إضافة متبرع جديد">
            <div class="space-y-4">
                <x-ui.input
                    label="اسم المتبرع / الجهة"
                    name="newDonorName"
                    wire:model="newDonorName"
                    placeholder="مثال: مؤسسة الخير"
                    required
                />
                <x-ui.select label="نوع المتبرع" name="newDonorType" wire:model="newDonorType" required>
                    @foreach($donorTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select label="المحافظة" name="newDonorCity" wire:model="newDonorCity" required>
                    <option value="">اختر المحافظة</option>
                    @foreach($governorateOptions as $gov)
                        <option value="{{ $gov }}">{{ $gov }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <x-ui.button variant="ghost" wire:click="closeNewDonorModal">إلغاء</x-ui.button>
                <x-ui.button variant="primary" wire:click="saveNewDonor" icon="check">حفظ المتبرع</x-ui.button>
            </div>
        </x-ui.modal>
    @endif
</div>
