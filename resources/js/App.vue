<template>
    <section class="bg-white h-full w-full flex">
        <!-- <transition name="fade"> -->
        <Sidebar class="no-print !flex-grow-0 !flex-shrink-0" />
        <!-- </transition>         -->
        <router-view class="flex-grow no-print" />
    </section>
</template>

<script setup>
import Sidebar from "./components/Sidebar.vue";

window.Echo.channel('time-channel').listen('TimeUpdated', (event) => {
        const serverTime = event.currentTime;
        console.log(serverTime);

        // if (serverTime) {
        //     columnDefs.value = computed(() => {
        //         return [
        //             {
        //                 headerName: t('table.graphictimes'),
        //                 headerClass: 'header-center',
        //                 children: [
        //                     {
        //                         headerName: t('table.startingTime'),
        //                         field: 'STime',
        //                         width: 120,
        //                         valueFormatter: (params) => format(new Date(`1970-01-01T${params.value}`), 'HH:mm'),
        //                         headerClass: 'header-center',
        //                     },
        //                     {
        //                         headerName: t('table.endingTime'),
        //                         field: 'ETime',
        //                         width: 120,
        //                         valueFormatter: (params) => format(new Date(`1970-01-01T${params.value}`), 'HH:mm'),
        //                         headerClass: 'header-center',
        //                     }
        //                 ]
        //             },
        //         ];
        //     }).value;
        // }
    });


</script>
<style>
@media print {
    .print {
        position: fixed;
        top: 0;
        left: 0;
        display: inherit;
    }

    .no-print {
        display: none;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.ag-header-container {
    background-color: rgb(209 213 219);
}

.ag-header-cell-text {
    color: black;
}

.ag-header-container {
    background-color: rgb(209 213 219);
}

.ag-header-cell-text {
    color: black;
}

::-webkit-scrollbar {
    width: 3px;
    height: 3px;
}

::-webkit-scrollbar-track {
    box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
}

::-webkit-scrollbar-thumb {
    background-color: #154EC1;
}
</style>