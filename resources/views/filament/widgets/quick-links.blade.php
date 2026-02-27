<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">
            @foreach ($this->getLinks() as $link)
            <a
                href="{{ $link['url'] }}"
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem 1rem; border-radius: 0.75rem; text-decoration: none; background: linear-gradient(135deg, rgba(7,50,178,0.06), rgba(26,0,107,0.04)); border: 1px solid rgba(7,50,178,0.12); transition: all 0.2s ease;"
                class="hover:shadow-lg group"
                onmouseover="this.style.background='linear-gradient(135deg, #0732b2, #1a006b)'; this.style.transform='translateY(-2px)'; this.querySelector('.ql-icon').style.color='#ffffff'; this.querySelector('.ql-label').style.color='#ffffff';"
                onmouseout="this.style.background='linear-gradient(135deg, rgba(7,50,178,0.06), rgba(26,0,107,0.04))'; this.style.transform='translateY(0)'; this.querySelector('.ql-icon').style.color=''; this.querySelector('.ql-label').style.color='';">
                <span class="ql-icon">
                    @svg($link['icon'], 'w-10 h-10 text-primary-500 transition-colors mb-3')
                </span>
                <span class="ql-label text-sm font-bold text-gray-700 dark:text-gray-200 text-center transition-colors">
                    {{ $link['label'] }}
                </span>
            </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>