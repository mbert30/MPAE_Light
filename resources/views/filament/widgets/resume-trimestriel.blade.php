<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>Résumé Trimestriel</span>
                <div class="flex items-center gap-2">
                    <x-filament::button 
                        size="sm" 
                        color="gray" 
                        wire:click="previousQuarter"
                        icon="heroicon-m-chevron-left"
                    >
                        Précédent
                    </x-filament::button>
                    
                    <span class="text-sm font-medium">{{ $this->getTrimestreData()['trimestre_label'] }}</span>
                    
                    <x-filament::button 
                        size="sm" 
                        color="gray" 
                        wire:click="nextQuarter"
                        icon="heroicon-m-chevron-right"
                    >
                        Suivant
                    </x-filament::button>
                </div>
            </div>
        </x-slot>

        @php
            $data = $this->getTrimestreData();
        @endphp

        <div class="space-y-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <strong>Période :</strong> {{ $data['periode'] }}
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="text-green-600 dark:text-green-400 text-sm font-medium">CA Payé</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                        {{ number_format($data['ca_paye'], 2, ',', ' ') }} €
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="text-blue-600 dark:text-blue-400 text-sm font-medium">CA Estimé</div>
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                        {{ number_format($data['ca_estime'], 2, ',', ' ') }} €
                    </div>
                </div>

                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                    <div class="text-orange-600 dark:text-orange-400 text-sm font-medium">Charges à Payer</div>
                    <div class="text-2xl font-bold text-orange-900 dark:text-orange-100">
                        {{ number_format($data['charges_a_payer'], 2, ',', ' ') }} €
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <div class="text-purple-600 dark:text-purple-400 text-sm font-medium">Charges Estimées</div>
                    <div class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                        {{ number_format($data['charges_estimees'], 2, ',', ' ') }} €
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>