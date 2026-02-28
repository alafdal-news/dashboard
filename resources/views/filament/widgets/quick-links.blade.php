<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
            @foreach ($this->getLinks() as $link)
            <a
                href="{{ $link['url'] }}"
                style="display: flex; flex-direction: row; align-items: center; gap: 0.625rem; padding: 0.75rem 1rem; border-radius: 0.5rem; text-decoration: none; background: linear-gradient(135deg, rgba(7,50,178,0.06), rgba(26,0,107,0.04)); border: 1px solid rgba(7,50,178,0.12); transition: all 0.2s ease; flex: 1 1 auto; min-width: 140px;"
                onmouseover="this.style.background='linear-gradient(135deg, #0732b2, #1a006b)'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'; this.querySelector('.ql-label').style.color='#ffffff'; this.querySelector('.ql-icon svg').style.color='#ffffff';"
                onmouseout="this.style.background='linear-gradient(135deg, rgba(7,50,178,0.06), rgba(26,0,107,0.04))'; this.style.transform='translateY(0)'; this.style.boxShadow='none'; this.querySelector('.ql-label').style.color='#374151'; this.querySelector('.ql-icon svg').style.color='#811619';">
                <span class="ql-icon" style="display: inline-flex; flex-shrink: 0;">
                    <x-filament::icon
                        :icon="$link['icon']"
                        class="w-5 h-5"
                        style="color: #811619;"
                    />
                </span>
                <span class="ql-label" style="font-size: 0.875rem; font-weight: 600; color: #374151; transition: color 0.2s ease;">
                    {{ $link['label'] }}
                </span>
            </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>