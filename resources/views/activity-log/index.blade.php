<x-layouts.app :title="__('Activity Log')">
    <div class="p-6 space-y-6">
        <flux:heading size="xl">{{ __('Activity Log') }}</flux:heading>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Type') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Description') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('User') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Date') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse ($activities as $activity)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ class_basename($activity->subject_type) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $activity->description }}
                                @if ($activity->subject)
                                    <span class="text-zinc-500 dark:text-zinc-400">
                                        - {{ $activity->subject->name }}
                                    </span>
                                    @if ($activity->subject->trashed())
                                        <flux:badge size="sm" color="red" class="ml-2">{{ __('Deleted') }}</flux:badge>
                                    @endif
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">
                                        - {{ __('Record no longer exists') }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $activity->causer?->name ?? __('System') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $activity->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <flux:text class="text-zinc-500 dark:text-zinc-400">
                                    {{ __('No activity logs yet.') }}
                                </flux:text>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activities->hasPages())
            <div class="mt-4">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
