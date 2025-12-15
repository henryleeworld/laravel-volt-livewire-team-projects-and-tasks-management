<x-layouts.app :title="__('View Task')">
    <div class="p-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ $task->name }}</flux:heading>
                <flux:text class="mt-2 text-zinc-600 dark:text-zinc-300">
                    {{ __('Task Details') }}
                </flux:text>
            </div>
            <div class="flex items-center gap-2">
                @can('update', $task)
                    <flux:button variant="primary" :href="route('tasks.edit', $task)" icon="pencil">
                        {{ __('Edit') }}
                    </flux:button>
                @endcan
                <flux:button variant="ghost" :href="route('tasks.index')">
                    {{ __('Back to Tasks') }}
                </flux:button>
            </div>
        </div>

        <div class="mx-auto max-w-2xl">
            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <dl class="space-y-6">
                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">{{ $task->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $task->description ?: __('No description provided.') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $task->created_at->format('M d, Y \a\t H:i') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Last Updated') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $task->updated_at->format('M d, Y \a\t H:i') }}
                        </dd>
                    </div>

                    @if($task->getMedia('images')->count() > 0)
                        <div>
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Images') }}</dt>
                            <dd class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3">
                                @foreach($task->getMedia('images') as $media)
                                    <a
                                        href="{{ $media->getUrl() }}"
                                        target="_blank"
                                        class="group relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                                    >
                                        <img
                                            src="{{ $media->getUrl('preview') }}"
                                            alt="{{ $media->name }}"
                                            class="h-32 w-full object-cover transition-transform group-hover:scale-105"
                                        />
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/0 transition-colors group-hover:bg-black/30">
                                            <flux:icon name="arrow-top-right-on-square" class="h-6 w-6 text-white opacity-0 transition-opacity group-hover:opacity-100" />
                                        </div>
                                    </a>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
            @if ($activities->isNotEmpty())
                <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="border-b border-zinc-200 bg-zinc-50 px-6 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:heading size="lg">{{ __('Recent Activity') }}</flux:heading>
                    </div>
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($activities as $activity)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $activity->description }}
                                        </flux:text>
                                        <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ __('By ') . $activity->causer?->name ?? __('System') }}
                                        </flux:text>
                                    </div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </flux:text>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>



