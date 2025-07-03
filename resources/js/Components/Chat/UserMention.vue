<template>
<div v-if="show" class="user-mention-dropdown bg-[#232B3E] border border-[#2A3446] rounded-lg shadow-lg absolute z-50 w-full md:w-64 max-h-48 md:max-h-64 overflow-y-auto bottom-full mb-2">
    <div class="p-2">
      <input 
        type="text" 
        v-model="searchQuery" 
        placeholder="Search users..."
        class="w-full px-3 py-2 border border-[#232B3E] rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 bg-[#232B3E] text-white placeholder-gray-400 text-sm"
        @keydown.down.prevent="selectNext"
        @keydown.up.prevent="selectPrevious"
        @keydown.enter.prevent="selectUser"
        @keydown.escape="$emit('close')"
        ref="searchInput"
      />
    </div>
    <div class="py-1">
      <!-- Team/Everyone option -->
      <div 
        class="flex items-center px-3 py-2 hover:bg-[#2A3446] cursor-pointer"
        :class="{ 'bg-[#2A3446]': selectedIndex === -1 }"
        @click="selectTeam"
        @mouseenter="selectedIndex = -1"
      >
        <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold mr-2">
          @
        </div>
        <div class="text-white">
          <div class="font-medium">Everyone</div>
          <div class="text-xs text-gray-400">Mention all group members</div>
        </div>
      </div>
      
      <!-- User list -->
      <div 
        v-for="(user, index) in filteredUsers" 
        :key="user.id"
        class="flex items-center px-3 py-2 hover:bg-[#2A3446] cursor-pointer"
        :class="{ 'bg-[#2A3446]': selectedIndex === index }"
        @click="selectMention(user)"
        @mouseenter="selectedIndex = index"
      >
        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-2">
          {{ user.name.charAt(0).toUpperCase() }}
        </div>
        <div class="text-white">
          <div class="font-medium">{{ user.name }}</div>
          <div class="text-xs text-gray-400">{{ user.email }}</div>
        </div>
      </div>
      
      <!-- Empty state -->
      <div v-if="filteredUsers.length === 0 && searchQuery" class="px-3 py-4 text-center text-gray-400">
        No users found
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  users: {
    type: Array,
    default: () => []
  },
  position: {
    type: Object,
    default: () => ({ top: 0, left: 0 })
  }
});

const emit = defineEmits(['select', 'close']);

const searchQuery = ref('');
const selectedIndex = ref(-1); // -1 for @team, 0+ for users
const searchInput = ref(null);

const filteredUsers = computed(() => {
  if (!searchQuery.value) return props.users;
  
  const query = searchQuery.value.toLowerCase();
  return props.users.filter(user => 
    user.name.toLowerCase().includes(query) || 
    user.email.toLowerCase().includes(query)
  );
});

// No need for responsive position anymore as we're using pure CSS positioning

// Focus the search input when dropdown is shown
watch(() => props.show, (newValue) => {
  if (newValue) {
    searchQuery.value = '';
    selectedIndex.value = -1; // Default to @team
    nextTick(() => {
      if (searchInput.value) {
        searchInput.value.focus();
      }
    });
  }
});

// Update selected index when filtered list changes
watch(filteredUsers, () => {
  if (selectedIndex.value >= filteredUsers.value.length) {
    selectedIndex.value = filteredUsers.value.length - 1;
  }
});

function selectNext() {
  if (selectedIndex.value < filteredUsers.value.length - 1) {
    selectedIndex.value++;
  } else {
    selectedIndex.value = -1; // Cycle back to @team
  }
}

function selectPrevious() {
  if (selectedIndex.value > -1) {
    selectedIndex.value--;
  } else {
    selectedIndex.value = filteredUsers.value.length - 1; // Cycle to last user
  }
}

function selectUser() {
  if (selectedIndex.value === -1) {
    selectTeam();
  } else if (selectedIndex.value >= 0 && filteredUsers.value[selectedIndex.value]) {
    selectMention(filteredUsers.value[selectedIndex.value]);
  }
}

function selectTeam() {
  emit('select', {
    id: 'team',
    name: 'Everyone',
    type: 'team'
  });
}

function selectMention(user) {
  emit('select', {
    ...user,
    type: 'user'
  });
}
</script>

<style scoped>
.user-mention-dropdown {
  max-height: 300px;
}

@media (max-width: 768px) {
  .user-mention-dropdown {
    width: calc(100% - 20px);
    left: 10px;
    right: 10px;
  }
}

.user-mention-dropdown {
  position: absolute;
  bottom: -15rem;
  left: 0;
  right: 0;
  transform: translateY(-100%);
  margin-bottom: 5px;
}
</style>
