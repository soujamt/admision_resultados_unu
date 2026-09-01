<div
    x-data="{
        // Estado
        show: false,
        type: 'info',
        title: '',
        message: '',
        confirmText: 'Aceptar',
        cancelText: 'Cancelar',
        showCancel: false,
        showConfirm: true,
        layout: 'default',
        action: null,
        recordLabel: 'Registro',
        recordName: '',
        description: '',
        resolveCallback: null,
        confirmEvent: null, // Evento al confirmar
        cancelEvent: null, // Evento al cancelar
        confirmParams: null, // Parámetros adicionales para el evento de confirmar
        cancelParams: null, // Parámetros adicionales para el evento de cancelar
        onResolve: null, // Callback de promesa para el dispatch global ($confirmar)

        // Mostrar alerta
        alert(options) {
            this.type = options.type || 'info';
            this.title = options.title || '';
            this.message = options.message || '';
            this.confirmText = options.confirmText || 'Aceptar';
            this.cancelText = options.cancelText || 'Cancelar';
            this.showCancel = options.showCancel || false;
            this.showConfirm = options.showConfirm !== false;
            this.layout = options.layout || 'default';
            this.action = options.action || null;
            this.recordLabel = options.recordLabel || 'Registro';
            this.recordName = options.recordName || '';
            this.description = options.description || '';
            this.confirmEvent = options.confirmEvent || null;
            this.cancelEvent = options.cancelEvent || null;
            this.confirmParams = options.confirmParams || null;
            this.cancelParams = options.cancelParams || null;
            this.onResolve = options.onResolve || null;
            this.show = true;

            // Retornar una promesa para manejar la respuesta
            return new Promise((resolve) => {
                this.resolveCallback = resolve;
            });
        },

        // Confirmar
        confirm() {
            this.show = false;

            // Si hay un evento definido, dispararlo
            if (this.confirmEvent) {
                // También dispatch como evento de Alpine/JS
                window.dispatchEvent(
                    new CustomEvent(this.confirmEvent, {
                        detail: this.confirmParams || {},
                    }),
                );
            }

            // Si hay callback de promesa, ejecutarlo
            if (this.resolveCallback) {
                this.resolveCallback(true);
            }

            // Resolver la promesa del dispatch global ($confirmar)
            if (this.onResolve) {
                this.onResolve(true);
                this.onResolve = null;
            }
        },

        // Cancelar
        cancel() {
            this.show = false;

            // Si hay un evento definido, dispararlo
            if (this.cancelEvent) {
                // También dispatch como evento de Alpine/JS
                window.dispatchEvent(
                    new CustomEvent(this.cancelEvent, {
                        detail: this.cancelParams || {},
                    }),
                );
            }

            // Si hay callback de promesa, ejecutarlo
            if (this.resolveCallback) {
                this.resolveCallback(false);
            }

            // Resolver la promesa del dispatch global ($confirmar)
            if (this.onResolve) {
                this.onResolve(false);
                this.onResolve = null;
            }
        },

        // Cerrar con backdrop
        closeOnBackdrop() {
            if (this.showCancel) {
                this.cancel();
            }
        },

        // Helpers rápidos
        success(title, message, confirmEvent = null, confirmParams = null) {
            return this.alert({
                type: 'success',
                title,
                message,
                confirmEvent,
                confirmParams,
            });
        },

        error(title, message, confirmEvent = null, confirmParams = null) {
            return this.alert({
                type: 'error',
                title,
                message,
                confirmEvent,
                confirmParams,
            });
        },

        warning(title, message, confirmEvent = null, confirmParams = null) {
            return this.alert({
                type: 'warning',
                title,
                message,
                confirmEvent,
                confirmParams,
            });
        },

        info(title, message, confirmEvent = null, confirmParams = null) {
            return this.alert({
                type: 'info',
                title,
                message,
                confirmEvent,
                confirmParams,
            });
        },

        question(title, message, confirmEvent = null, cancelEvent = null, confirmParams = null, cancelParams = null) {
            return this.alert({
                type: 'question',
                title,
                message,
                showCancel: true,
                confirmText: 'Sí',
                cancelText: 'No',
                confirmEvent,
                cancelEvent,
                confirmParams,
                cancelParams,
            });
        },

        recordAction(options) {
            return this.alert({
                layout: 'record-action',
                type: options.type || 'question',
                action: options.action || null,
                title: options.title || '',
                message: options.message || '',
                description: options.description || '',
                recordLabel: options.recordLabel || 'Registro',
                recordName: options.recordName || '',
                showCancel: true,
                confirmText: options.confirmText || 'Confirmar',
                cancelText: options.cancelText || 'Cancelar',
                confirmEvent: options.confirmEvent || null,
                cancelEvent: options.cancelEvent || null,
                confirmParams: options.confirmParams || null,
                cancelParams: options.cancelParams || null,
                onResolve: options.onResolve || null,
            });
        },
    }"
    x-on:alert.window="alert($event.detail)"
    x-on:alert-record-action.window="recordAction($event.detail)"
    x-on:alert-success.window="
        success($event.detail.title, $event.detail.message, $event.detail.confirmEvent, $event.detail.confirmParams)
    "
    x-on:alert-error.window="
        error($event.detail.title, $event.detail.message, $event.detail.confirmEvent, $event.detail.confirmParams)
    "
    x-on:alert-warning.window="
        warning($event.detail.title, $event.detail.message, $event.detail.confirmEvent, $event.detail.confirmParams)
    "
    x-on:alert-info.window="
        info($event.detail.title, $event.detail.message, $event.detail.confirmEvent, $event.detail.confirmParams)
    "
    x-on:alert-question.window="
        question(
            $event.detail.title,
            $event.detail.message,
            $event.detail.confirmEvent,
            $event.detail.cancelEvent,
            $event.detail.confirmParams,
            $event.detail.cancelParams,
        )
    "
    x-on:keydown.escape.window="show && cancel()"
>
    <!-- Backdrop -->
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeOnBackdrop()"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm"
        style="display: none"
    ></div>

    <!-- Modal -->
    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        @click.stop
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none"
    >
        <div
            class="w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900"
            :class="layout === 'record-action' ? 'max-w-lg' : 'max-w-md'"
        >
            <div
                x-show="layout === 'record-action'"
                class="flex items-center justify-between border-b border-zinc-100 px-6 py-5 dark:border-white/10"
            >
                <h3 x-text="title" class="text-base font-semibold text-slate-950 dark:text-zinc-100"></h3>
                <button
                    type="button"
                    @click="cancel()"
                    class="flex size-8 items-center justify-center rounded-md text-slate-400 transition-colors hover:bg-zinc-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-zinc-200"
                    aria-label="Cerrar alerta"
                >
                    <flux:icon.x-mark variant="micro" />
                </button>
            </div>

            <!-- Icono -->
            <div x-show="layout === 'default'" class="flex justify-center pt-8 pb-4">
                <div class="relative">
                    <!-- Success -->
                    <template x-if="type === 'success'">
                        <flux:icon.check-circle variant="solid" class="size-20 text-green-500 dark:text-green-400" />
                    </template>

                    <!-- Error -->
                    <template x-if="type === 'error'">
                        <flux:icon.x-circle variant="solid" class="size-20 text-red-500 dark:text-red-400" />
                    </template>

                    <!-- Warning -->
                    <template x-if="type === 'warning'">
                        <x-icons.advertencia class="size-20 text-yellow-500 dark:text-yellow-400" />
                    </template>

                    <!-- Info -->
                    <template x-if="type === 'info'">
                        <flux:icon.information-circle variant="solid" class="size-20 text-sky-500 dark:text-sky-400" />
                    </template>

                    <!-- Question -->
                    <template x-if="type === 'question'">
                        <flux:icon.question-mark-circle variant="solid" class="size-20 text-purple-600 dark:text-purple-400" />
                    </template>
                </div>
            </div>

            <!-- Contenido -->
            <div x-show="layout === 'default'" class="px-8 pb-6 text-center">
                <h3
                    x-show="title"
                    x-text="title"
                    class="mb-2 text-xl font-semibold text-zinc-900 dark:text-zinc-100"
                ></h3>
                <p
                    x-show="message"
                    x-text="message"
                    class="text-normal text-sm leading-relaxed text-zinc-600 dark:text-zinc-400"
                ></p>
            </div>

            <div x-show="layout === 'record-action'" class="px-8 pt-10 pb-8 text-center">
                <div class="mb-8 flex justify-center">
                    <template x-if="action === 'enable'">
                        <x-icons.candado abierto class="size-20 text-green-600 dark:text-green-400" />
                    </template>

                    <template x-if="action === 'disable'">
                        <x-icons.candado class="size-20 text-red-500 dark:text-red-400" />
                    </template>

                    <template x-if="action === 'delete'">
                        <x-icons.trash class="size-20 text-red-500 dark:text-red-400" />
                    </template>

                    <template x-if="action === 'reset-password'">
                        <x-icons.llave class="size-20 text-sky-600 dark:text-sky-400" />
                    </template>

                    <template x-if="! ['enable', 'disable', 'delete', 'reset-password'].includes(action)">
                        <flux:icon.question-mark-circle variant="solid" class="size-20 text-sky-500 dark:text-sky-400" />
                    </template>
                </div>

                <p x-show="message" x-text="message" class="text-base leading-snug text-slate-950 dark:text-zinc-100"></p>
                <p
                    x-show="description"
                    x-text="description"
                    class="mx-auto mt-5 max-w-sm text-base leading-snug text-slate-500 dark:text-zinc-400"
                ></p>

                <div x-show="recordName" class="mt-7 flex items-center justify-center gap-3 text-base">
                    <span x-text="recordLabel + ':'" class="font-semibold text-slate-950 dark:text-zinc-100"></span>
                    <span x-text="recordName" class="font-medium text-slate-500 dark:text-zinc-400"></span>
                </div>
            </div>

            <!-- Botones -->
            <div
                class="flex gap-3 px-6 pb-6"
                :class="layout === 'record-action' ? 'justify-center' : { 'justify-center': !showCancel, 'justify-end': showCancel }"
            >
                <flux:button x-show="showCancel" @click="cancel()" variant="filled" class="min-w-30 font-normal">
                    <span x-text="cancelText"></span>
                </flux:button>

                <!-- Success -->
                <flux:button
                    x-show="showConfirm && type === 'success' && layout === 'default'"
                    @click="confirm()"
                    variant="primary"
                    color="green"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <!-- Error -->
                <flux:button
                    x-show="showConfirm && type === 'error' && layout === 'default'"
                    @click="confirm()"
                    variant="primary"
                    color="red"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <!-- Warning -->
                <flux:button
                    x-show="showConfirm && type === 'warning' && layout === 'default'"
                    @click="confirm()"
                    variant="primary"
                    color="yellow"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <!-- Info -->
                <flux:button
                    x-show="showConfirm && type === 'info' && layout === 'default'"
                    @click="confirm()"
                    variant="primary"
                    color="sky"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <!-- Question -->
                <flux:button
                    x-show="showConfirm && type === 'question' && layout === 'default'"
                    @click="confirm()"
                    variant="primary"
                    color="purple"
                    class="min-w-25 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <flux:button
                    x-show="showConfirm && layout === 'record-action' && action === 'enable'"
                    @click="confirm()"
                    variant="primary"
                    color="green"
                    class="min-w-30 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <flux:button
                    x-show="showConfirm && layout === 'record-action' && action === 'reset-password'"
                    @click="confirm()"
                    variant="primary"
                    color="sky"
                    class="min-w-37.5 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>

                <flux:button
                    x-show="showConfirm && layout === 'record-action' && ! ['enable', 'reset-password'].includes(action)"
                    @click="confirm()"
                    variant="primary"
                    color="red"
                    class="min-w-30 font-normal"
                >
                    <span x-text="confirmText"></span>
                </flux:button>
            </div>
        </div>
    </div>
</div>
