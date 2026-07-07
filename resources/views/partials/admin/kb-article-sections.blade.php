@props(['article', 'lang' => 'en'])

@php
    $suffix = $lang === 'bn' ? '_bn' : '_en';
    $isBn = $lang === 'bn';
    $sections = [
        [
            'key'     => 'purpose',
            'title'   => $isBn ? '১. এই মডিউল/স্ক্রিনের কাজ কী ঃ' : '1. Purpose — what this module/screen does',
            'content' => $article->{'purpose' . $suffix},
        ],
        [
            'key'     => 'audience',
            'title'   => $isBn ? '২. কে ব্যবহার করবে · কোন department ঃ' : '2. Who uses it · which department',
            'content' => $article->{'audience' . $suffix},
        ],
        [
            'key'     => 'usage_rules',
            'title'   => $isBn ? '৩. ব্যবহার বিধি ঃ' : '3. Usage rules & guidelines',
            'content' => $article->{'usage_rules' . $suffix},
        ],
    ];
    $contentClass = $isBn ? 'kb-bn-content prose prose-sm max-w-none text-gray-800' : 'prose prose-sm max-w-none text-gray-800';
@endphp

<div class="space-y-4 {{ $isBn ? 'font-anek-bangla' : '' }}">
    @foreach($sections as $section)
        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
            <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-800">{{ $section['title'] }}</h2>
            </div>
            <div class="px-4 py-3 {{ $contentClass }}">
                @if($section['content'])
                    {!! $section['content'] !!}
                @else
                    <p class="text-sm text-gray-400 italic">
                        {{ $isBn ? 'এই অংশে এখনো তথ্য যোগ করা হয়নি।' : 'No content added for this section yet.' }}
                    </p>
                @endif
            </div>
        </div>
    @endforeach
</div>
