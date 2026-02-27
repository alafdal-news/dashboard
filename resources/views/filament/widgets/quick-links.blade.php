<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
            @foreach ($this->getLinks() as $link)
            <a
                href="{{ $link['url'] }}"
                style="display: flex; flex-direction: row; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; background: linear-gradient(135deg, rgba(7,50,178,0.06), rgba(26,0,107,0.04)); border: 1px solid rgba(7,50,178,0.12); transition: all 0.2s ease;"
                class="hover:shadow-md group"
                onmouseover="this.style.background='linear-gradient(135deg, #0732b2, #1a006b)'; this.style.transform='translateY(-1px)'; this.querySelector('.ql-label').style.color='#ffffff'; var s=this.querySelector('.ql-icon svg'); if(s){s.style.color='#ffffff';}"
                onmouseout="this.style.background='linear-gradient(135deg, rgba(7,50,178,0.06), rgba(26,0,107,0.04))'; this.style.transform='translateY(0)'; this.querySelector('.ql-label').style.color=''; var s=this.querySelector('.ql-icon svg'); if(s){s.style.color='';}">
                <span class="ql-icon shrink-0" style="display:inline-flex;">
                    <x-filament::icon
                        :icon="$link['icon']"
                        class="w-5 h-5"
                        style="color: #811619;"
                    />
                </span>
                <span class="ql-label text-sm font-semibold text-gray-700 dark:text-gray-200 transition-colors">
                    {{ $link['label'] }}
                </span>
            </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>