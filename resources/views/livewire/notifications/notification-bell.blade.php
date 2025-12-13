<div wire:poll.30s>
    <flux:dropdown position="top" align="end">
        <flux:button variant="ghost" size="sm" class="relative !h-10" icon="bell">
            @if($this->unreadCount > 0)
                <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                    {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Notifications') }}</h3>
                    @if($this->unreadCount > 0)
                        <flux:button wire:click="markAllAsRead" variant="ghost" size="sm" class="text-xs">
                            {{ __('Mark all as read') }}
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse($this->notifications as $notification)
                    <a
                        href="{{ $notification->data['action_url'] }}"
                        class="block px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 border-b border-zinc-100 dark:border-zinc-800 transition-colors"
                        wire:navigate
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <flux:icon name="clipboard-document-list" class="size-5 text-zinc-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate">
                                    {{ $notification->data['task_name'] }}
                                </p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                                    {{ __('Assigned by :assigner_name', ['assigner_name' => $notification->data['assigner_name']]) }}
                                </p>
                                @if($notification->data['task_description'])
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1 line-clamp-2">
                                        {{ $notification->data['task_description'] }}
                                    </p>
                                @endif
                                <p class="text-xs text-zinc-400 dark:text-zinc-600 mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-4 py-8 text-center">
                        <flux:icon name="bell-slash" class="size-12 mx-auto text-zinc-300 dark:text-zinc-700 mb-2" />
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No new notifications') }}</p>
                    </div>
                @endforelse
            </div>
        </flux:menu>
    </flux:dropdown>
</div>
