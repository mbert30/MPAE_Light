<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-filament::icon
                    icon="heroicon-m-information-circle"
                    class="h-5 w-5 text-gray-400 dark:text-gray-500"
                />
                <span>Informations système</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            <!-- Date de création -->
            <div class="space-y-2">
                <div class="flex items-center gap-x-2">
                    <x-filament::icon
                        icon="heroicon-m-calendar-days"
                        class="h-4 w-4 text-gray-400 dark:text-gray-500"
                    />
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Créé le
                    </span>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 ml-6">
                    {{ $dateCreation }}
                </div>
            </div>

            <!-- Dernière modification -->
            <div class="space-y-2">
                <div class="flex items-center gap-x-2">
                    <x-filament::icon
                        icon="heroicon-m-pencil"
                        class="h-4 w-4 text-gray-400 dark:text-gray-500"
                    />
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Dernière modification
                    </span>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 ml-6">
                    {{ $derniereModification }}
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>