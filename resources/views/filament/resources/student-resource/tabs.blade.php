<div>
    <x-filament::tabs>
        @foreach ($tabs as $tabKey => $tab)
            @php
                $group = $tab->getGroup();
                $previousTab = $loop->index > 0 ? $tabs[$loop->index - 1] : null;
                $previousGroup = $previousTab?->getGroup();
                $isFirstInGroup = $previousGroup !== $group;
                $nextTab = $loop->index < count($tabs) - 1 ? $tabs[$loop->index + 1] : null;
                $nextGroup = $nextTab?->getGroup();
                $isLastInGroup = $nextGroup !== $group;
            @endphp

            @if($isFirstInGroup && $group)
                <div class="mt-2 mb-1 font-medium text-sm">
                    {{ $group }}
                </div>
            @endif

            <x-filament::tabs.item
                :active="$activeTab === $tabKey"
                :badge="$tab->getBadge()"
                :icon="$tab->getIcon()"
                :icon-position="$tab->getIconPosition()"
                :wire:click="'$set(\'activeTab\', \'' . $tabKey . '\')'"
                :class="$group ? 'ml-4' : ''"
            >
                {{ $tab->getLabel() }}
            </x-filament::tabs.item>

            @if($isLastInGroup && $group)
                <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
            @endif
        @endforeach
    </x-filament::tabs>
</div>
