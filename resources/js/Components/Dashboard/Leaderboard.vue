<template>
    <div class="bg-gray-800 p-4 rounded shadow-lg">
        <h2 class="text-xl font-bold text-white mb-4">اعضای کلن</h2>
        <table class="w-full text-left text-gray-300">
            <thead class="bg-gray-700">
            <tr>
                <th class="p-2">نام</th>
                <th class="p-2">رول</th>
                <th class="p-2">کاپ</th>
                <th class="p-2">دونیت</th>
            </tr>
            </thead>
            <tbody>
            <tr
                v-for="(member, index) in members"
                :key="index"
                class="border-b border-gray-600"
            >
                <td class="p-2">{{ member.name }}</td>
                <td class="p-2">{{ member.role }}</td>
                <td class="p-2">{{ member.trophies }}</td>
                <td class="p-2">{{ member.donations }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: 'ClanMembers',
    props: {
        members: {
            type: Array,
            default: () => []
        }
    }
}
</script>
