<template>
  <div class="chat-container h-full">
    <div class="flex h-full">
      <!-- User List Sidebar -->
      <div class="w-1/4 bg-gray-100 border-r border-gray-200 overflow-y-auto relative">
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
        
        <!-- Create Group Button -->
        <div class="absolute bottom-4 right-4">
          <button 
            @click="openCreateGroupModal" 
            class="bg-blue-500 hover:bg-blue-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg focus:outline-none"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>
      </div>
      
      <!-- Chat Area -->
      <div class="w-3/4 flex flex-col h-full">
        <div v-if="!currentConversation" class="flex-1 flex items-center justify-center bg-gray-50">
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
            <div v-if="conversations.length === 0" class="flex items-center justify-center h-full">
              <p class="text-gray-500">No messages yet. Start the conversation!</p>
            </div>
            <div v-else v-for="(message, index) in conversations" :key="index" class="mb-4">
              <div 
                :class="[
                  'max-w-xs rounded-lg p-3 mb-2', 
                  message.user_id === currentUser.id 
                    ? 'bg-blue-500 text-white ml-auto' 
                    : 'bg-gray-200 text-gray-800'
                ]"
              >
                <template v-if="message.type === 'file'">
                  <a :href="fileUrl(message.file_path || message.message)" target="_blank" class="underline break-all">
                    <template v-if="isImageFile(message.file_path || message.message)">
                      <img :src="fileUrl(message.file_path || message.message)" :alt="message.message" class="max-h-40 max-w-xs rounded mb-1" />
                    </template>
                    <span>{{ getFileName(message.file_path || message.message) }}</span>
                  </a>
                </template>
                <template v-else>
                  {{ message.message }}
                </template>
              </div>
              <div 
                :class="[
                  'text-xs text-gray-500', 
                  message.user_id === currentUser.id ? 'text-right' : 'text-left'
                ]"
              >
                {{ message.user ? message.user.name : 'Unknown' }} • {{ formatTime(message.created_at) }}
              </div>
            </div>
          </div>
          
          <!-- Message Input -->
          <div class="p-4 border-t border-gray-200 bg-white">
            <form enctype="multipart/form-data" @submit.prevent="sendMessage" class="flex items-center space-x-2">
              <input 
                type="text" 
                v-model="newMessage" 
                placeholder="Type a message..." 
                class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                :disabled="fileToSend"
              >
              <label class="inline-flex items-center cursor-pointer bg-gray-100 px-2 py-2 rounded hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a4 4 0 10-5.656-5.656l-6.586 6.586" />
                </svg>
                <input type="file" class="hidden" @change="onFileChange" ref="fileInput" />
              </label>
              <button 
                type="submit" 
                class="px-4 py-2 bg-blue-500 text-white rounded-r-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :disabled="(!newMessage.trim() && !fileToSend)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </form>
            <div v-if="fileToSend" class="mt-2 flex items-center space-x-2 bg-gray-100 p-2 rounded">
              <span class="truncate max-w-xs">{{ fileToSend.name }}</span>
              <button @click="removeFile" class="text-red-500 hover:text-red-700" title="Remove file">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create Group Modal -->
    <div v-if="showCreateGroupModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg p-6 w-96 max-w-full">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Create New Group</h2>
          <button @click="showCreateGroupModal = false" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="group-name">
            Group Name
          </label>
          <input 
            type="text" 
            id="group-name"
            v-model="groupName"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter group name"
          >
        </div>
        
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">
            Select Users
          </label>
          <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-lg p-2">
            <div 
              v-for="user in groupUsers" 
              :key="user.id" 
              class="flex items-center p-2 hover:bg-gray-100 rounded"
            >
              <input 
                type="checkbox" 
                :id="'user-' + user.id" 
                :value="user.id" 
                v-model="selectedGroupUsers"
                class="mr-2"
              >
              <label :for="'user-' + user.id" class="flex items-center cursor-pointer">
                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-2">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="font-medium">{{ user.name }}</div>
                  <div class="text-xs text-gray-500">{{ user.email }}</div>
                </div>
              </label>
            </div>
          </div>
        </div>
        
        <div class="flex justify-end">
          <button 
            @click="showCreateGroupModal = false" 
            class="px-4 py-2 text-gray-600 mr-2 hover:text-gray-800"
          >
            Cancel
          </button>
          <button 
            @click="createGroup" 
            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
            :disabled="!groupName.trim() || selectedGroupUsers.length === 0"
          >
            Create Group
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const currentUser = ref(null);
const searchResults = ref({ users: [], groups: [] });
const selectedItem = ref(null);
const currentConversation = ref(null);
const conversations = ref([]);
const newMessage = ref('');
const searchQuery = ref('');
const activeTab = ref('all');
const isLoading = ref(true);
const hasError = ref(false);
const errorMessage = ref('');
const messagesContainer = ref(null);

// Group creation
const showCreateGroupModal = ref(false);
const groupUsers = ref([]);
const selectedGroupUsers = ref([]);
const groupName = ref('');

const fileToSend = ref(null);
const fileInput = ref(null);

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

async function getOrCreateIndividualConversation(userId) {
  isLoading.value = true;
  try {
    const res = await axios.get('/conversations', { params: { user_id: userId } });
    currentConversation.value = res.data.conversation;
    conversations.value = res.data.messages || [];
    isLoading.value = false;
    scrollToBottom();
    
    // Listen for new messages in this conversation
    listenForNewMessages(currentConversation.value.id);
  } catch (e) {
    isLoading.value = false;
  }
}

async function getGroupConversation(groupId) {
  isLoading.value = true;
  try {
    const res = await axios.get(`/conversations/${groupId}`);
    currentConversation.value = res.data.conversation;
    conversations.value = res.data.messages || [];
    isLoading.value = false;
    scrollToBottom();
    
    // Listen for new messages in this conversation
    listenForNewMessages(currentConversation.value.id);
  } catch (e) {
    isLoading.value = false;
  }
}

function selectUser(user) {
  selectedItem.value = { ...user, type: 'user' };
  getOrCreateIndividualConversation(user.id);
}

function selectGroup(group) {
  selectedItem.value = { ...group, type: 'group' };
  getGroupConversation(group.id);
}

function listenForNewMessages(conversationId) {
  // First, leave any existing channels
  if (window.Echo) {
    try {
      // Clean up any existing listeners to avoid duplicates
      window.Echo.leave(`conversation.${conversationId}`);
    } catch (error) {
      console.error('Error leaving channel:', error);
    }
  } else {
    console.error('Echo is not initialized');
    return;
  }
  
  // Then, listen to the new channel
  console.log(`Listening to private-conversation.${conversationId} channel`);
  
  try {
    window.Echo.private(`conversation.${conversationId}`)
      .listen('.NewMessage', (event) => {
        console.log('New message received (with dot):', event);
        handleNewMessage(event);
      })
      .listen('NewMessage', (event) => {
        console.log('New message received (without dot):', event);
        handleNewMessage(event);
      })
      .error((error) => {
        console.error('Echo connection error:', error);
      });
  } catch (error) {
    console.error('Error setting up Echo listener:', error);
  }
}

function handleNewMessage(event) {
  // Add the new message to the conversation
  if (event.message && currentConversation.value && 
      event.message.conversation_id === currentConversation.value.id) {
    // Check if the message is already in the conversation
    const messageExists = conversations.value.some(m => m.id === event.message.id);
    if (!messageExists) {
      conversations.value.push(event.message);
      scrollToBottom();
    }
  }
}

// Clean up function to leave channels when component is unmounted
function leaveChannels() {
  if (currentConversation.value && window.Echo) {
    try {
      console.log(`Leaving channel: conversation.${currentConversation.value.id}`);
      window.Echo.leave(`conversation.${currentConversation.value.id}`);
    } catch (error) {
      console.error('Error leaving channel:', error);
    }
  }
}

function onFileChange(e) {
  const file = e.target.files[0];
  if (file) {
    fileToSend.value = file;
    newMessage.value = '';
  }
}

function removeFile() {
  fileToSend.value = null;
  if (fileInput.value) fileInput.value.value = '';
}

function fileUrl(path) {
  if (!path) return '';
  // Assuming files are stored in public disk
  return path.startsWith('/') ? path : `/storage/${path}`;
}

function isImageFile(path) {
  if (!path) return false;
  return /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(path);
}

function getFileName(path) {
  if (!path) return 'File';
  // Extract just the filename from the path (after the last slash)
  const filename = path.split('/').pop();
  // Remove the timestamp prefix if present (format: YYYYMMDDHHmmss_filename.ext)
  return filename.replace(/^\d{14}_/, '');
}

async function sendMessage() {
  if ((!newMessage.value.trim() && !fileToSend.value) || !currentConversation.value) return;
  try {
    const formData = new FormData();
    formData.append('conversation_id', currentConversation.value.id);
    if (fileToSend.value) {
      formData.append('file', fileToSend.value);
    }
    if (newMessage.value.trim()) {
      formData.append('message', newMessage.value);
    }
    
    console.log('Sending message:', fileToSend.value ? 'with file' : 'text only');
    
    const res = await axios.post('/conversations', formData, {
      headers: { 
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json'
      }
    });
    
    fileToSend.value = null;
    if (fileInput.value) fileInput.value.value = '';
    conversations.value.push(res.data);
    newMessage.value = '';
    scrollToBottom();
  } catch (e) {
    console.error('Error sending message:', e.response ? e.response.data : e);
  }
}

function openCreateGroupModal() {
  showCreateGroupModal.value = true;
  groupName.value = '';
  selectedGroupUsers.value = [];
  // Load all users for selection
  axios.get('/users')
    .then(res => {
      groupUsers.value = res.data.users || res.data;
    })
    .catch(error => {
      console.error('Error loading users for group creation:', error);
    });
}

async function createGroup() {
  if (!groupName.value.trim() || selectedGroupUsers.value.length === 0) return;
  
  isLoading.value = true;
  try {
    const res = await axios.post('/groups/create-or-get', {
      name: groupName.value,
      user_ids: selectedGroupUsers.value
    });
    
    // Close modal and reset values
    showCreateGroupModal.value = false;
    selectedGroupUsers.value = [];
    groupName.value = '';
    
    // Refresh the groups list
    getSearchResults();
    
    // Select the newly created group
    if (res.data && res.data.group) {
      selectGroup(res.data.group);
    }
    
    isLoading.value = false;
  } catch (e) {
    console.error('Error creating group:', e);
    isLoading.value = false;
  }
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

// Set up CSRF protection
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Initialize component
onMounted(async () => {
  try {
    await axios.get('/sanctum/csrf-cookie');
    await getCurrentUser();
    getSearchResults();
  } catch (error) {
    console.error('Initialization error:', error);
    hasError.value = true;
    errorMessage.value = 'Failed to load user data. Please refresh the page.';
    isLoading.value = false;
  }
});

// Clean up when component is unmounted
onUnmounted(() => {
  leaveChannels();
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