<template>
  <div class="chat-container h-full">
    <div class="flex h-full">
      <!-- User List Sidebar -->
      <div class="w-1/4 bg-gray-100 border-r border-gray-200 overflow-y-auto">
        <div class="p-4 border-b border-gray-200">
          <div class="relative">
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Search users and groups..." 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
            <div v-if="searchQuery" class="absolute right-3 top-2.5 cursor-pointer text-gray-500" @click="searchQuery = ''">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </div>
          </div>
        </div>
        
        <!-- Tab navigation -->
        <div class="flex border-b border-gray-200">
          <button 
            @click="activeTab = 'all'" 
            class="flex-1 py-3 text-center font-medium"
            :class="activeTab === 'all' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'"
          >
            All
          </button>
          <button 
            @click="activeTab = 'users'" 
            class="flex-1 py-3 text-center font-medium"
            :class="activeTab === 'users' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'"
          >
            Users
          </button>
          <button 
            @click="activeTab = 'groups'" 
            class="flex-1 py-3 text-center font-medium"
            :class="activeTab === 'groups' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500'"
          >
            Groups
          </button>
        </div>
        
        <!-- Debug info - remove in production -->
        <div class="p-2 bg-yellow-100 text-xs">
          <p>Users: {{ searchResults.users ? searchResults.users.length : 0 }}</p>
          <p>Groups: {{ searchResults.groups ? searchResults.groups.length : 0 }}</p>
          <p>Filtered Users: {{ filteredItems.users ? filteredItems.users.length : 0 }}</p>
          <p>Filtered Groups: {{ filteredItems.groups ? filteredItems.groups.length : 0 }}</p>
          <p>Search: "{{ searchQuery }}"</p>
          <p>Tab: {{ activeTab }}</p>
        </div>
        
        <!-- Loading indicator -->
        <div v-if="isLoading" class="p-4 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
          <p class="mt-2 text-gray-500">Loading...</p>
        </div>
        
        <!-- Error message -->
        <div v-else-if="hasError" class="p-4 text-center text-red-500">
          {{ errorMessage }}
        </div>
        
        <div v-else class="user-list">
          <!-- Users list -->
          <div 
            v-for="user in filteredItems.users" 
            :key="'user-' + user.id" 
            @click="selectUser(user)"
            class="p-4 border-b border-gray-200 hover:bg-gray-200 cursor-pointer flex items-center"
            :class="{'bg-blue-50': selectedItem && selectedItem.id === user.id && selectedItem.type === 'user'}"
          >
            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-3">
              {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="font-medium">{{ user.name }}</div>
              <div class="text-sm text-gray-500">{{ user.email }}</div>
            </div>
          </div>
          
          <!-- Groups list -->
          <div 
            v-for="group in filteredItems.groups" 
            :key="'group-' + group.id" 
            @click="selectGroup(group)"
            class="p-4 border-b border-gray-200 hover:bg-gray-200 cursor-pointer flex items-center"
            :class="{'bg-blue-50': selectedItem && selectedItem.id === group.id && selectedItem.type === 'group'}"
          >
            <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mr-3">
              {{ group.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="font-medium">{{ group.name }}</div>
              <div class="text-sm text-gray-500">{{ group.users.length }} members</div>
            </div>
          </div>
          
          <!-- No results message -->
          <div v-if="(filteredItems.users.length === 0 && filteredItems.groups.length === 0)" class="p-4 text-center text-gray-500">
            No results found
          </div>
        </div>
      </div>
      
      <!-- Chat Area -->
      <div class="w-3/4 flex flex-col h-full">
        <div v-if="!currentGroup" class="flex-1 flex items-center justify-center bg-gray-50">
          <div class="text-center text-gray-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-xl">Select a user or group to start chatting</p>
          </div>
        </div>
        
        <div v-else class="flex flex-col h-full">
          <!-- Chat Header -->
          <div class="p-4 border-b border-gray-200 bg-white flex items-center">
            <div 
              class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold mr-3"
              :class="selectedItem.type === 'user' ? 'bg-blue-500' : 'bg-green-500'"
            >
              {{ selectedItem.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="font-medium">{{ selectedItem.name }}</div>
              <div class="text-sm text-gray-500">
                <span v-if="selectedItem.type === 'user'">{{ selectedItem.online ? 'Online' : 'Offline' }}</span>
                <span v-else>{{ selectedItem.users.length }} members</span>
              </div>
            </div>
          </div>
          
          <!-- Messages Area -->
          <div class="flex-1 p-4 overflow-y-auto bg-gray-50" ref="messagesContainer">
            <div v-for="(message, index) in conversations" :key="index" class="mb-4">
              <div 
                :class="[
                  'max-w-xs rounded-lg p-3 mb-2', 
                  message.user.id === currentUser.id 
                    ? 'bg-blue-500 text-white ml-auto' 
                    : 'bg-gray-200 text-gray-800'
                ]"
              >
                {{ message.message }}
              </div>
              <div 
                :class="[
                  'text-xs text-gray-500', 
                  message.user.id === currentUser.id ? 'text-right' : 'text-left'
                ]"
              >
                {{ message.user.name }} • {{ formatTime(message.created_at) }}
              </div>
            </div>
          </div>
          
          <!-- Message Input -->
          <div class="p-4 border-t border-gray-200 bg-white">
            <form @submit.prevent="sendMessage" class="flex">
              <input 
                type="text" 
                v-model="newMessage" 
                placeholder="Type a message..." 
                class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
              <button 
                type="submit" 
                class="px-4 py-2 bg-blue-500 text-white rounded-r-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :disabled="!newMessage.trim()"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import axios from 'axios';

const currentUser = ref(null);
const searchResults = ref({ users: [], groups: [] });
const selectedItem = ref(null);
const currentGroup = ref(null);
const conversations = ref([]);
const newMessage = ref('');
const searchQuery = ref('');
const activeTab = ref('all');
const isLoading = ref(true);
const hasError = ref(false);
const errorMessage = ref('');
const messagesContainer = ref(null);

const filteredItems = computed(() => {
  const result = { users: [], groups: [] };
  if (!searchResults.value.users || !searchResults.value.groups) {
    return result;
  }
  if (!searchQuery.value) {
    if (activeTab.value === 'users') {
      return { users: searchResults.value.users || [], groups: [] };
    } else if (activeTab.value === 'groups') {
      return { users: [], groups: searchResults.value.groups || [] };
    } else {
      return searchResults.value;
    }
  }
  const query = searchQuery.value.toLowerCase();
  const filteredUsers = activeTab.value === 'groups' ? [] :
    (searchResults.value.users || []).filter(user =>
      user.name.toLowerCase().includes(query) ||
      user.email.toLowerCase().includes(query)
    );
  const filteredGroups = activeTab.value === 'users' ? [] :
    (searchResults.value.groups || []).filter(group =>
      group.name.toLowerCase().includes(query)
    );
  return {
    users: filteredUsers,
    groups: filteredGroups
  };
});

function getCurrentUser() {
  isLoading.value = true;
  return axios.get('/user')
    .then(response => {
      currentUser.value = response.data;
    })
    .catch(error => {
      throw error;
    });
}

function getSearchResults() {
  axios.get('/search')
    .then(response => {
      searchResults.value = response.data;
      isLoading.value = false;
    })
    .catch(error => {
      isLoading.value = false;
    });
}

function selectUser(user) {
  selectedItem.value = { ...user, type: 'user' };
  createOrGetGroup(user.id);
}

function selectGroup(group) {
  selectedItem.value = { ...group, type: 'group' };
  currentGroup.value = group;
  getConversations(group.id);
  listenForNewMessage(group.id);
}

function createOrGetGroup(userId) {
  axios.post('/groups/create-or-get', {
    user_id: userId
  })
    .then(response => {
      currentGroup.value = response.data;
      getConversations(currentGroup.value.id);
      listenForNewMessage(currentGroup.value.id);
    })
    .catch(error => {
      // Handle error silently
    });
}

function getConversations(groupId) {
  if (!groupId) return;
  axios.get(`/conversations/${groupId}`)
    .then(response => {
      conversations.value = response.data;
      scrollToBottom();
    })
    .catch(error => {
      conversations.value = [];
    });
}

function sendMessage() {
  if (!newMessage.value.trim() || !currentGroup.value) return;
  axios.post('/conversations', {
    message: newMessage.value,
    group_id: currentGroup.value.id
  })
    .then(response => {
      conversations.value.push(response.data);
      newMessage.value = '';
      scrollToBottom();
    })
    .catch(error => {
      // Handle error silently
    });
}

function listenForNewMessage(groupId) {
  if (!groupId) return;
  if (window.Echo) {
    window.Echo.leave('private-groups.' + groupId);
  }
  window.Echo.private('groups.' + groupId)
    .listen('NewMessage', (e) => {
      conversations.value.push(e.conversation);
      scrollToBottom();
    });
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
}

function formatTime(timestamp) {
  if (!timestamp) return '';
  const date = new Date(timestamp);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

watch(currentGroup, (newGroup, oldGroup) => {
  if (oldGroup && window.Echo) {
    window.Echo.leave('private-groups.' + oldGroup.id);
  }
  if (newGroup) {
    listenForNewMessage(newGroup.id);
  }
});

onMounted(async () => {
  try {
    await axios.get('/sanctum/csrf-cookie');
    await getCurrentUser();
    getSearchResults();
  } catch (error) {
    hasError.value = true;
    errorMessage.value = 'Failed to load user data. Please refresh the page.';
    isLoading.value = false;
  }
});
</script>

<style scoped>
.chat-container {
  height: 70vh;
}

.user-list {
  max-height: calc(70vh - 150px);
  overflow-y: auto;
}

::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style> 