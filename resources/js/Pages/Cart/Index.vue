<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    total: {
        type: [Number, String],
        required: true,
    },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const errors = computed(() => page.props.errors || {});

const quantities = reactive(
    Object.fromEntries(props.items.map((item) => [item.id, item.quantity])),
);

const updateItem = (item) => {
    router.patch(
        route('cart.update', item.id),
        { quantity: quantities[item.id] },
        { preserveScroll: true },
    );
};

const removeItem = (item) => {
    router.delete(route('cart.destroy', item.id), { preserveScroll: true });
};

const checkout = () => {
    router.post(route('cart.checkout'));
};
</script>

<template>
    <Head title="Cart" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Cart
            </h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
                <div v-if="flash.success" class="mb-4 rounded-md bg-green-50 p-4 text-green-700">
                    {{ flash.success }}
                </div>
                <div v-if="errors.checkout" class="mb-4 rounded-md bg-red-50 p-4 text-red-700">
                    {{ errors.checkout }}
                </div>
                <div v-if="errors.quantity" class="mb-4 rounded-md bg-red-50 p-4 text-red-700">
                    {{ errors.quantity }}
                </div>

                <div v-if="items.length === 0" class="rounded-lg bg-white p-6 shadow-sm">
                    Your cart is empty.
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ item.product.name }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                ${{ Number(item.product.price).toFixed(2) }} each
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                v-model.number="quantities[item.id]"
                                type="number"
                                min="1"
                                class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <button
                                class="rounded-md border border-indigo-600 px-3 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50"
                                @click="updateItem(item)"
                            >
                                Update
                            </button>
                            <button
                                class="rounded-md border border-red-600 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                                @click="removeItem(item)"
                            >
                                Remove
                            </button>
                        </div>

                        <div class="text-right text-sm font-semibold text-gray-700">
                            ${{ Number(item.subtotal).toFixed(2) }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-white p-6 shadow-sm">
                        <div class="text-lg font-semibold text-gray-900">
                            Total
                        </div>
                        <div class="text-lg font-semibold text-gray-900">
                            ${{ Number(total).toFixed(2) }}
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            class="rounded-md bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-500"
                            @click="checkout"
                        >
                            Place order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
