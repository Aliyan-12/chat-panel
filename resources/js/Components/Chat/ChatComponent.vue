<template>
  <div class="chat-container h-full bg-[#181F2A] flex">
    <!-- Sidebar -->
    <aside class="w-80 bg-[#232B3E] border-r border-[#232B3E] flex flex-col h-full shadow-lg">
      <div class="p-4 border-b border-[#232B3E] flex items-center justify-between">
        <span class="text-xl font-bold text-orange-400 tracking-tight">Chat</span>
        <button @click="openCreateGroupModal" class="bg-orange-500 hover:bg-orange-600 text-white rounded-full w-10 h-10 flex items-center justify-center shadow focus:outline-none transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
        </button>
      </div>
      <div class="p-3">
            <input 
              type="text" 
              v-model="searchQuery" 
          placeholder="Search chats..."
          class="w-full px-4 py-2 border border-[#232B3E] rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 bg-[#232B3E] text-sm text-white placeholder-gray-400"
            >
            </div>
      <!-- Filter Tabs -->
      <div class="flex border-b border-[#232B3E] mb-1">
        <button @click="activeTab = 'all'" class="flex-1 py-2 text-center font-medium transition"
          :class="activeTab === 'all' ? 'text-orange-400 border-b-2 border-orange-400 bg-[#232B3E]' : 'text-gray-400'">
            All
          </button>
        <button @click="activeTab = 'users'" class="flex-1 py-2 text-center font-medium transition"
          :class="activeTab === 'users' ? 'text-orange-400 border-b-2 border-orange-400 bg-[#232B3E]' : 'text-gray-400'">
            Users
          </button>
        <button @click="activeTab = 'groups'" class="flex-1 py-2 text-center font-medium transition"
          :class="activeTab === 'groups' ? 'text-orange-400 border-b-2 border-orange-400 bg-[#232B3E]' : 'text-gray-400'">
            Groups
          </button>
        </div>
      <nav class="flex-1 overflow-y-auto">
        <div v-for="chat in filteredChatList" :key="chat.id"
          @click="chat.type === 'group' ? selectGroup(chat.group) : selectUser(chat.user)"
          class="flex items-center p-3 rounded-lg hover:bg-[#222B45] transition group cursor-pointer relative mb-1"
          :class="{'bg-[#222B45] border-l-4 border-orange-400': selectedItem && ((chat.type === 'user' && selectedItem.id === chat.user?.id) || (chat.type === 'group' && selectedItem.id === chat.group?.id))}"
        >
          <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg mr-3 shadow"
            :class="chat.type === 'group' ? 'bg-gradient-to-br from-orange-400 to-orange-600' : 'bg-gradient-to-br from-blue-400 to-blue-600'">
            {{ chat.type === 'group' ? chat.group.name.charAt(0).toUpperCase() : chat.user.name.charAt(0).toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-center">
              <span class="font-semibold truncate text-white">{{ chat.type === 'group' ? chat.group.name : chat.user.name }}</span>
              <span class="text-xs text-gray-400 ml-2">{{ chat.last_message ? formatTime(chat.last_message.created_at) : '' }}</span>
            </div>
            <div class="text-sm text-gray-400 truncate">{{ chat.last_message ? chat.last_message.message : 'No messages yet' }}</div>
          </div>
          <span v-if="chat.unread_count > 0" class="ml-2 bg-orange-500 text-white text-xs rounded-full px-2 py-0.5 animate-pulse shadow">{{ chat.unread_count }}</span>
        </div>
        <div v-if="filteredChatList.length === 0" class="p-4 text-center text-gray-400">No chats found</div>
      </nav>
    </aside>

    <!-- Main Chat Area -->
    <section class="flex-1 flex flex-col h-full">
      <header v-if="currentConversation" class="p-4 border-b border-gray-200 bg-[#232B3E] flex items-center shadow-sm">
        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg mr-3 shadow"
          :class="selectedItem.type === 'user' ? 'bg-gradient-to-br from-blue-400 to-blue-600' : 'bg-gradient-to-br from-orange-400 to-orange-600'">
          {{ selectedItem.name.charAt(0).toUpperCase() }}
        </div>
        <div class="flex-1">
          <div class="text-white font-semibold text-lg group relative cursor-pointer" v-if="selectedItem.type === 'group'">
            {{ selectedItem.name }}
            <!-- Tooltip for group members -->
            <div class="absolute left-0 top-full z-50 mt-2 w-64 rounded-lg bg-[#232B3E] p-3 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 border border-gray-200">
              <h3 class="text-sm font-medium text-orange-500 mb-2">Group Members ({{ selectedItem.users.length }})</h3>
              <ul class="max-h-48 overflow-y-auto">
                <li v-for="user in selectedItem.users" :key="user.id" class="flex items-center py-1">
                  <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-xs mr-2">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>
                  <span class="text-sm text-white">{{ user.name }}</span>
                  <button 
                    v-if="currentUser.id !== user.id" 
                    @click="removeParticipant(user.id)" 
                    class="ml-auto text-red-500 hover:text-red-700"
                    title="Remove from group"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </li>
              </ul>
            </div>
          </div>
          <div class="text-white font-semibold text-lg" v-else>
            {{ selectedItem.name }}
          </div>
          <div class="text-xs text-[#c9d0dd]">
            <span v-if="selectedItem.type === 'user'">{{ selectedItem.online ? 'Online' : 'Offline' }}</span>
            <span v-else>{{ selectedItem.users.length }} members</span>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <!-- Search Messages Button -->
          <button @click="toggleSearchMessages" class="p-2 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </button>
          <!-- Add Participants Button (only for groups) -->
          <button v-if="selectedItem.type === 'group'" @click="openAddParticipantsModal" class="p-2 bg-gray-100 text-gray-600 rounded-full hover:bg-gray-200 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
          </button>
        </div>
      </header>
      <div v-else class="flex-1 flex items-center justify-center bg-[#181F2A]">
        <div class="text-center text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-xl">Select a user or group to start chatting</p>
        </div>
      </div>
          
      <!-- Messages Area -->
      <main v-if="currentConversation" class="flex-1 p-6 overflow-y-auto bg-[#181F2A]" ref="messagesContainer">
        <div v-if="conversations.length === 0" class="flex items-center justify-center h-full">
          <p class="text-gray-400">No messages yet. Start the conversation!</p>
        </div>
        <div v-else v-for="(message, index) in conversations" :key="index" :id="`message-${message.id}`" class="mb-6 flex flex-col"
          :class="{'items-end': message.user_id === currentUser.id, 'items-start': message.user_id !== currentUser.id, 'items-center': message.is_system}">
          <div v-if="message.is_system" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-xs italic">
            {{ message.message }}
          </div>
          <div v-else class="flex items-end max-w-lg">
            <div v-if="message.user_id !== currentUser.id" class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-2">
              {{ message.user ? message.user.name.charAt(0).toUpperCase() : '?' }}
            </div>
            <div :class="[
              'rounded-2xl px-4 py-2 shadow',
              message.user_id === currentUser.id ? 'bg-orange-500 text-white' : 'bg-[#232B3E] text-white border border-[#2A3446]'
            ]">
                <template v-if="message.type === 'file'">
                  <a :href="fileUrl(message.file_path || message.message)" target="_blank" class="underline break-all">
                    <template v-if="isImageFile(message.file_path || message.message)">
                      <img :src="fileUrl(message.file_path || message.message)" :alt="getFileName(message.file_path || message.message)" class="max-h-40 max-w-xs rounded mb-1" />
                    </template>
                    <span>{{ getFileName(message.file_path || message.message) }}</span>
                  </a>
                  <div v-if="message.message && getFileName(message.file_path || message.message) !== message.message" v-html="message.message" class="html-message mt-1"></div>
                </template>
                <template v-else>
                  <div v-html="message.message" class="html-message"></div>
                </template>
              </div>
            <div v-if="message.user_id === currentUser.id" class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold ml-2">
              {{ currentUser.name.charAt(0).toUpperCase() }}
            </div>
          </div>
          <span v-if="!message.is_system" class="text-xs text-gray-400 mt-1" :class="{'text-right': message.user_id === currentUser.id, 'text-left': message.user_id !== currentUser.id}">
            {{ message.user ? message.user.name : 'Unknown' }} • {{ formatTime(message.created_at) }}
          </span>
        </div>
        
        <!-- Typing indicator -->
        <div v-if="isTyping" class="flex items-start mb-4">
          <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-2">
            {{ typingUser.charAt(0).toUpperCase() }}
          </div>
          <div class="bg-[#232B3E] text-white border border-[#2A3446] rounded-2xl px-4 py-3 shadow">
            <div class="typing-indicator">
              <span></span>
              <span></span>
              <span></span>
            </div>
          </div>
        </div>
      </main>
          
      <!-- Message Input -->
      <footer v-if="currentConversation" class="p-4 border-t border-[#232B3E] bg-[#181F2A] flex items-center space-x-2 shadow-inner">
        <form enctype="multipart/form-data" @submit.prevent="sendMessage" class="flex items-center w-full space-x-2">
          <label class="inline-flex items-center cursor-pointer bg-[#232B3E] px-3 py-2 rounded-lg hover:bg-[#2A3446] transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a4 4 0 10-5.656-5.656l-6.586 6.586" />
                </svg>
                <input type="file" class="hidden" @change="onFileChange" ref="fileInput" />
              </label>
          <div 
            ref="messageInput"
            contenteditable="true"
            class="flex-1 px-4 py-2 border border-[#232B3E] rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 bg-[#232B3E] text-white text-sm placeholder-gray-400 overflow-y-auto"
            :placeholder="fileToSend ? '' : 'Type a message... (Enter to send, Shift+Enter for new line)'"
            @input="handleInput"
            @paste="handlePaste"
            @keydown="handleKeyDown"
            role="textbox"
            aria-multiline="true"
          ></div>
          <button 
            type="submit" 
            class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400 transition"
            :class="{ 'opacity-50 cursor-not-allowed': isMessageEmpty && !fileToSend }"
            :disabled="isMessageEmpty && !fileToSend"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </form>
        <div v-if="fileToSend" class="ml-2 flex items-center space-x-2 bg-[#232B3E] p-2 rounded-lg text-white">
          <span class="truncate max-w-xs">{{ fileToSend.name }}</span>
          <button @click="removeFile" class="text-red-500 hover:text-red-400" title="Remove file">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </footer>
    </section>

    <!-- Toaster Notification -->
    <transition name="fade">
      <div
        v-if="toaster.show"
        @click="onToasterClick"
        class="fixed z-50 right-6 bottom-6 bg-[#232B3E] border border-orange-400 text-white px-6 py-4 rounded-lg shadow-xl flex items-center space-x-3 animate-fade-in-up cursor-pointer"
        style="min-width: 280px; max-width: 380px;"
      >
        <div class="text-orange-400 mr-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
          </svg>
        </div>
        <div class="flex-1">
          <div class="font-bold flex items-center">
            <span v-if="toaster.group" class="text-orange-400">{{ toaster.group }} - </span>
            <span>{{ toaster.sender }}</span>
            <span class="ml-2 text-xs bg-orange-500 text-white px-2 py-0.5 rounded-full">New</span>
          </div>
          <div class="text-sm mt-1">
            {{ toaster.message }}
          </div>
          <div class="text-xs text-gray-300 mt-1 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ toaster.time }}
          </div>
        </div>
        <button @click.stop="dismissToaster" class="ml-2 text-gray-400 hover:text-white focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </transition>

    <!-- Add Participants Modal -->
    <div v-if="showAddParticipantsModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
      <div class="bg-[#181F2A] border border-[#232B3E] rounded-lg shadow-xl p-6 w-96 max-w-full">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold text-white">Add Participants</h2>
          <button @click="showAddParticipantsModal = false" class="text-gray-400 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="mb-4">
          <input type="text" v-model="addParticipantsSearch" placeholder="Search users..." class="w-full px-3 py-2 border border-[#232B3E] rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 bg-[#232B3E] text-white placeholder-gray-400" />
        </div>
        <div class="max-h-60 overflow-y-auto border border-[#232B3E] rounded-lg p-2 mb-4">
          <div v-for="user in filteredAddParticipantsUsers" :key="user.id" class="flex items-center p-2 hover:bg-[#232B3E] rounded">
            <input type="checkbox" :id="'add-user-' + user.id" :value="user.id" v-model="selectedAddParticipants" class="mr-2 rounded border-[#232B3E] bg-[#232B3E] text-orange-500 focus:ring-orange-400" />
            <label :for="'add-user-' + user.id" class="flex items-center cursor-pointer">
              <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold mr-2">
                {{ user.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <div class="font-medium text-white">{{ user.name }}</div>
                <div class="text-xs text-gray-400">{{ user.email }}</div>
              </div>
            </label>
          </div>
          <div v-if="filteredAddParticipantsUsers.length === 0" class="text-gray-400 text-center py-2">No users found</div>
        </div>
        <div class="flex justify-end">
          <button @click="showAddParticipantsModal = false" class="px-4 py-2 text-gray-400 mr-2 hover:text-white">Cancel</button>
          <button @click="addParticipantsToGroup" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400" :disabled="selectedAddParticipants.length === 0">Add</button>
        </div>
      </div>
    </div>
    
    <!-- Create Group Modal -->
    <div v-if="showCreateGroupModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
      <div class="bg-[#181F2A] border border-[#232B3E] rounded-lg shadow-xl p-6 w-96 max-w-full">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold text-white">Create New Group</h2>
          <button @click="showCreateGroupModal = false" class="text-gray-400 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="mb-4">
          <input type="text" id="group-name" v-model="groupName" class="w-full px-3 py-2 border border-[#232B3E] rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 bg-[#232B3E] text-white placeholder-gray-400" placeholder="Enter group name" />
        </div>
        <div class="mb-4">
          <input type="text" v-model="groupUserSearch" placeholder="Search users..." class="w-full px-3 py-2 border border-[#232B3E] rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 bg-[#232B3E] text-white placeholder-gray-400 mb-2" />
          <div class="max-h-60 overflow-y-auto border border-[#232B3E] rounded-lg p-2">
            <div v-for="user in filteredGroupUsers" :key="user.id" class="flex items-center p-2 hover:bg-[#232B3E] rounded">
              <input type="checkbox" :id="'user-' + user.id" :value="user.id" v-model="selectedGroupUsers" class="mr-2 rounded border-[#232B3E] bg-[#232B3E] text-orange-500 focus:ring-orange-400" />
              <label :for="'user-' + user.id" class="flex items-center cursor-pointer">
                <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold mr-2">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="font-medium text-white">{{ user.name }}</div>
                  <div class="text-xs text-gray-400">{{ user.email }}</div>
                </div>
              </label>
            </div>
            <div v-if="filteredGroupUsers.length === 0" class="text-gray-400 text-center py-2">No users found</div>
          </div>
        </div>
        <div class="flex justify-end">
          <button @click="showCreateGroupModal = false" class="px-4 py-2 text-gray-400 mr-2 hover:text-white">Cancel</button>
          <button @click="createGroup" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-400" :disabled="!groupName.trim() || selectedGroupUsers.length === 0">Create Group</button>
        </div>
      </div>
    </div>

    <!-- Search Messages Panel -->
    <div v-if="showSearchMessages && currentConversation" class="p-3 border-b border-[#232B3E] bg-[#181F2A] flex flex-col">
      <div class="flex items-center">
        <div class="relative flex-1">
          <input 
            type="text" 
            v-model="messageSearchQuery" 
            placeholder="Search in this conversation..."
            class="w-full px-4 py-2 pr-10 border border-[#232B3E] rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 bg-[#232B3E] text-sm text-white placeholder-gray-400"
            @keyup.enter="searchMessages"
          >
          <button @click="clearMessageSearch" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <button @click="searchMessages" class="ml-2 p-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </button>
      </div>
      
      <!-- Search Results -->
      <div v-if="searchResults.length > 0" class="mt-2 text-gray-300 text-sm flex items-center justify-between">
        <div>{{ searchResultIndex + 1 }} of {{ searchResults.length }} matches</div>
        <div class="flex">
          <button @click="navigateToPreviousResult" class="p-1 hover:text-orange-400" :disabled="searchResultIndex === 0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
          </button>
          <button @click="navigateToNextResult" class="p-1 hover:text-orange-400" :disabled="searchResultIndex === searchResults.length - 1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
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

const notifications = ref([]);
const toaster = ref({
  show: false,
  message: '',
  shortMessage: '',
  sender: '',
  group: '',
  time: '',
  conversation_id: null,
  group_id: null,
  user_id: null,
  message_id: null,
  timeout: null,
});

const chatList = ref([]); // All conversations with unread counts

const showAddParticipantsModal = ref(false);
const addParticipantsSearch = ref('');
const addParticipantsUsers = ref([]); // Users not in group
const selectedAddParticipants = ref([]);

// For group creation modal search
const groupUserSearch = ref('');

const isTyping = ref(false);
const typingUser = ref('');
const typingTimeout = ref(null);
const typingTimer = ref(null);

// Add these variables for search functionality
const showSearchMessages = ref(false);
const messageSearchQuery = ref('');
const isSearching = ref(false);
const searchResultIndex = ref(0);

// Add these variables for contenteditable message input
const messageInput = ref(null);
const messageContent = ref('');

// Add isMessageEmpty computed property
const isMessageEmpty = computed(() => {
  if (!messageContent.value) return true;
  
  // Check if the content is just whitespace, &nbsp;, or <br> tags
  const cleanContent = messageContent.value
    .replace(/<br\s*\/?>/gi, '')
    .replace(/&nbsp;/g, '')
    .trim();
    
  return cleanContent === '';
});

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

const filteredChatList = computed(() => {
  let list = chatList.value;
  if (activeTab.value === 'users') {
    list = list.filter(c => c.type === 'individual');
  } else if (activeTab.value === 'groups') {
    list = list.filter(c => c.type === 'group');
  }
  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(c => {
    if (c.type === 'group') {
      return c.group.name.toLowerCase().includes(q);
    } else if (c.type === 'individual') {
      return c.user.name.toLowerCase().includes(q) || c.user.email.toLowerCase().includes(q);
    }
    return false;
  });
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

async function getChatList() {
  isLoading.value = true;
  try {
    const res = await axios.get('/conversations');
    chatList.value = res.data.conversations || [];
    isLoading.value = false;
  } catch (e) {
    isLoading.value = false;
  }
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

async function selectUser(user) {
  selectedItem.value = { ...user, type: 'user' };
  await getOrCreateIndividualConversation(user.id);
  await markChatAsRead(currentConversation.value.id);
  updateChatListOnOpen(currentConversation.value.id);
}

async function selectGroup(group) {
  selectedItem.value = { ...group, type: 'group' };
  await getGroupConversation(group.id);
  await markChatAsRead(currentConversation.value.id);
  updateChatListOnOpen(currentConversation.value.id);
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
      
    // Also listen for typing indicators
    listenForTypingIndicators(conversationId);
  } catch (error) {
    console.error('Error setting up Echo listener:', error);
  }
}

function handleNewMessage(event) {
  if (event.message && event.message.conversation_id) {
    // Find chat in chatList
    const idx = chatList.value.findIndex(c => c.id === event.message.conversation_id);
    if (idx !== -1) {
      // If not currently open, increment unread count
      if (!currentConversation.value || currentConversation.value.id !== event.message.conversation_id) {
        chatList.value[idx].unread_count = (chatList.value[idx].unread_count || 0) + 1;
      }
      // Move chat to top
      const chat = chatList.value.splice(idx, 1)[0];
      chatList.value.unshift(chat);
    } else {
      // If not found, refetch chatList
      getChatList();
    }
  }
  // Add the new message to the conversation if open
  if (event.message && currentConversation.value && 
      event.message.conversation_id === currentConversation.value.id) {
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

// Add these functions for handling HTML paste and input
function handleInput(e) {
  // Store the raw HTML content
  messageContent.value = e.target.innerHTML;
  
  // Clear content if it's just a single <br> tag
  if (messageContent.value === '<br>') {
    messageContent.value = '';
  }
  
  // console.log('Input HTML content:', messageContent.value);
  sendTypingIndicator();
}

function handlePaste(e) {
  e.preventDefault();
  
  // Get clipboard data
  const clipboardData = e.clipboardData || window.clipboardData;
  
  // Check if there are files (images) in the clipboard
  if (clipboardData.files && clipboardData.files.length > 0) {
    const file = clipboardData.files[0];
    if (file.type.startsWith('image/')) {
      fileToSend.value = file;
      return;
    }
  }
  
  try {
    // Handle HTML content
    let html = clipboardData.getData('text/html');
    let text = clipboardData.getData('text/plain');
    
    // If HTML is available, use it, otherwise use plain text
    if (html && html.trim() !== '') {
      // Sanitize HTML to prevent XSS
      const sanitizedHtml = sanitizeHtml(html);
      document.execCommand('insertHTML', false, sanitizedHtml);
    } else if (text) {
      // For plain text, preserve line breaks by converting them to <br> tags
      const textWithLineBreaks = text.replace(/\n/g, '<br>');
      document.execCommand('insertHTML', false, textWithLineBreaks);
    }
    
    // Update the message content value
    nextTick(() => {
      messageContent.value = messageInput.value.innerHTML;
    });
  } catch (error) {
    console.error('Error handling paste:', error);
    // Fallback to simple text insertion
    const text = clipboardData.getData('text/plain');
    if (text) {
      const textWithLineBreaks = text.replace(/\n/g, '<br>');
      document.execCommand('insertHTML', false, textWithLineBreaks);
      messageContent.value = messageInput.value.innerHTML;
    }
  }
}

// Enhanced HTML sanitizer function
function sanitizeHtml(html) {
  // Create a temporary div
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = html;
  
  // Remove potentially harmful elements
  const scriptsAndEvents = tempDiv.querySelectorAll('script, style, iframe, object, embed, [on*=]');
  scriptsAndEvents.forEach(el => el.remove());
  
  // Keep only allowed tags and attributes
  const allowedTags = ['p', 'br', 'b', 'i', 'u', 'strong', 'em', 'span', 'div'];
  const allElements = tempDiv.querySelectorAll('*');
  
  allElements.forEach(el => {
    // If not in allowed tags list, replace with its text content
    if (!allowedTags.includes(el.tagName.toLowerCase())) {
      // Keep the text content
      const textContent = el.textContent;
      const textNode = document.createTextNode(textContent);
      el.parentNode.replaceChild(textNode, el);
    } else {
      // Remove all attributes except a few safe ones
      const allowedAttrs = ['class'];
      Array.from(el.attributes).forEach(attr => {
        if (!allowedAttrs.includes(attr.name)) {
          el.removeAttribute(attr.name);
        }
      });
    }
  });
  
  // Preserve line breaks, spaces, and basic formatting
  let cleanHtml = tempDiv.innerHTML
    .replace(/\n/g, '<br>')
    .replace(/  +/g, match => '&nbsp;'.repeat(match.length));
  
  return cleanHtml;
}

// Add this function to ensure HTML content is properly preserved
function getMessageHtml() {
  // Get the raw HTML content from the contenteditable div
  let html = messageInput.value ? messageInput.value.innerHTML : '';
  
  // Make sure line breaks are properly represented as <br> tags
  html = html.replace(/\n/g, '<br>');
  
  // If the content is just a single <br> or empty, return empty string
  if (html === '<br>' || html === '' || html.trim() === '') {
    return '';
  }
  
  return html;
}

// Update the sendMessage function to use messageContent with HTML preserved
async function sendMessage() {
  // Get the HTML content
  const htmlMessage = getMessageHtml();
  
  // Check if the message is empty (only contains whitespace, &nbsp;, or <br> tags)
  const isEmptyMessage = !htmlMessage.trim() || 
                         htmlMessage.replace(/<br\s*\/?>/gi, '').replace(/&nbsp;/g, '').trim() === '';
  
  // If both message and file are empty, show error and return
  if (isEmptyMessage && !fileToSend.value) {
    // Show popup notification
    showNotification('Message cannot be empty', 'error');
    return;
  }
  
  if (!currentConversation.value) return;
  
  try {
    const formData = new FormData();
    formData.append('conversation_id', currentConversation.value.id);
    
    // Always append both fields, even if one is empty
    formData.append('file', fileToSend.value || '');
    formData.append('message', htmlMessage);
    
    // console.log('Sending message:', fileToSend.value ? 'with file' : 'text only');
    // console.log('Message HTML content:', htmlMessage);
    
    const res = await axios.post('/conversations', formData, {
      headers: { 
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json'
      }
    });
    
    fileToSend.value = null;
    if (fileInput.value) fileInput.value.value = '';
    conversations.value.push(res.data);
    messageContent.value = '';
    if (messageInput.value) messageInput.value.innerHTML = '';
    scrollToBottom();
  } catch (e) {
    console.error('Error sending message:', e.response ? e.response.data : e);
    showNotification('Failed to send message', 'error');
  }
}

// Add a notification function
function showNotification(message, type = 'info') {
  // Create notification element
  const notification = document.createElement('div');
  notification.className = `notification fixed z-50 bottom-4 left-1/2 transform -translate-x-1/2 px-4 py-2 rounded-lg shadow-lg text-white ${type === 'error' ? 'bg-red-500' : 'bg-green-500'}`;
  notification.textContent = message;
  
  // Add to document
  document.body.appendChild(notification);
  
  // Trigger animation
  setTimeout(() => {
    notification.classList.add('notification-enter-active');
  }, 10);
  
  // Remove after 3 seconds
  setTimeout(() => {
    notification.classList.add('notification-leave-active');
    notification.classList.add('opacity-0');
    notification.style.transform = 'translate(-50%, 20px)';
    
    setTimeout(() => {
      if (notification.parentNode) {
        document.body.removeChild(notification);
      }
    }, 300);
  }, 3000);
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

// Listen for real-time notifications
function listenForNotifications(userId) {
  if (!window.Echo || !userId) return;
  window.Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
      notifications.value.unshift(notification);
      showToaster(notification);
    });
}

function showToaster(notification) {
  toaster.value.show = true;
  toaster.value.message = notification.message;
  toaster.value.shortMessage = getShortMessage(notification.message);
  toaster.value.sender = notification.sender_name;
  toaster.value.group = notification.group_name || '';
  toaster.value.time = formatTime(notification.created_at);
  toaster.value.conversation_id = notification.conversation_id || null;
  toaster.value.group_id = notification.group_id || null;
  toaster.value.user_id = notification.sender_id || null;
  toaster.value.message_id = notification.message_id || null;
  if (toaster.value.timeout) clearTimeout(toaster.value.timeout);
  toaster.value.timeout = setTimeout(() => {
    toaster.value.show = false;
  }, 10000); // 10 seconds
}

function dismissToaster() {
  toaster.value.show = false;
  if (toaster.value.timeout) clearTimeout(toaster.value.timeout);
}

function getShortMessage(msg) {
  if (!msg) return '';
  return msg.split(' ').slice(0, 2).join(' ') + (msg.split(' ').length > 2 ? '...' : '');
}

async function onToasterClick() {
  toaster.value.show = false;
  if (toaster.value.group_id) {
    // It's a group message
    const group = (searchResults.value.groups || []).find(g => g.id === toaster.value.group_id);
    if (group) {
      await selectGroup(group);
    }
  } else if (toaster.value.user_id) {
    // It's a direct message
    const user = (searchResults.value.users || []).find(u => u.id === toaster.value.user_id);
    if (user) {
      await selectUser(user);
    }
  }
  // Wait for messages to load, then scroll to the message
  nextTick(() => {
    scrollToMessage(toaster.value.message_id);
  });
}

function scrollToMessage(messageId) {
  nextTick(() => {
    const messageEl = document.getElementById(`message-${messageId}`);
    if (messageEl) {
      messageEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      messageEl.classList.add('highlight');
      setTimeout(() => messageEl.classList.remove('highlight'), 2000);
    }
  });
}

async function markChatAsRead(conversationId) {
  try {
    await axios.post('/conversations/read', { conversation_id: conversationId });
  } catch (e) {}
}

function updateChatListOnOpen(conversationId) {
  // Reset unread count and move chat to top
  const idx = chatList.value.findIndex(c => c.id === conversationId);
  if (idx !== -1) {
    chatList.value[idx].unread_count = 0;
    const chat = chatList.value.splice(idx, 1)[0];
    chatList.value.unshift(chat);
  }
}

async function openAddParticipantsModal() {
  if (!currentConversation.value || !currentConversation.value.group_id) return;
  showAddParticipantsModal.value = true;
  addParticipantsSearch.value = '';
  selectedAddParticipants.value = [];
  // Fetch all users not in the group
  const groupId = currentConversation.value.group_id;
  try {
    const res = await axios.get('/users');
    const groupUsers = selectedItem.value.users.map(u => u.id);
    addParticipantsUsers.value = (res.data.users || res.data).filter(u => !groupUsers.includes(u.id));
  } catch (e) {
    addParticipantsUsers.value = [];
  }
}

async function addParticipantsToGroup() {
  if (!currentConversation.value || !currentConversation.value.group_id || selectedAddParticipants.value.length === 0) return;
  try {
    await axios.post(`/groups/${currentConversation.value.group_id}/add-users`, {
      user_ids: selectedAddParticipants.value
    });
    showAddParticipantsModal.value = false;
    // Refresh group info
    await selectGroup(selectedItem.value);
  } catch (e) {
    // handle error
  }
}

const filteredAddParticipantsUsers = computed(() => {
  if (!addParticipantsSearch.value) return addParticipantsUsers.value;
  const q = addParticipantsSearch.value.toLowerCase();
  return addParticipantsUsers.value.filter(u =>
    u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );
});

const filteredGroupUsers = computed(() => {
  if (!groupUserSearch.value) return groupUsers.value;
  const q = groupUserSearch.value.toLowerCase();
  return groupUsers.value.filter(u =>
    u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );
});

// Initialize component
onMounted(async () => {
  try {
    await axios.get('/sanctum/csrf-cookie');
    await getCurrentUser();
    await getChatList();
    getSearchResults();
    // Fetch existing notifications
    const notifRes = await axios.post('/notifications');
    notifications.value = notifRes.data.data || [];
    // Listen for real-time notifications
    if (currentUser.value && currentUser.value.id) {
      listenForNotifications(currentUser.value.id);
    }
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

// Add this function after listenForNewMessages
function listenForTypingIndicators(conversationId) {
  if (window.Echo) {
    try {
      window.Echo.private(`conversation.${conversationId}`)
        .listenForWhisper('typing', (e) => {
          if (e.user_id !== currentUser.value.id) {
            isTyping.value = true;
            typingUser.value = e.name;
            
            // Clear any existing timeout
            if (typingTimeout.value) clearTimeout(typingTimeout.value);
            
            // Set a new timeout to clear the typing indicator after 3 seconds
            typingTimeout.value = setTimeout(() => {
              isTyping.value = false;
            }, 3000);
          }
        });
    } catch (error) {
      console.error('Error setting up typing indicator:', error);
    }
  }
}

// Update the sendTypingIndicator function
function sendTypingIndicator() {
  // Clear the existing timer
  if (typingTimer.value) clearTimeout(typingTimer.value);
  
  // Set a new timer to send the typing indicator
  typingTimer.value = setTimeout(() => {
    if (window.Echo && currentConversation.value) {
      try {
        window.Echo.private(`conversation.${currentConversation.value.id}`)
          .whisper('typing', {
            user_id: currentUser.value.id,
            name: currentUser.value.name
          });
      } catch (error) {
        console.error('Error sending typing indicator:', error);
      }
    }
  }, 300); // 300ms debounce
}

// Add these functions for search functionality
function toggleSearchMessages() {
  showSearchMessages.value = !showSearchMessages.value;
  if (!showSearchMessages.value) {
    clearMessageSearch();
  }
}

function searchMessages() {
  if (!messageSearchQuery.value.trim() || !currentConversation.value) return;
  
  isSearching.value = true;
  searchResults.value = conversations.value.filter(message => 
    message.message && message.message.toLowerCase().includes(messageSearchQuery.value.toLowerCase())
  );
  
  // Reset search result index
  searchResultIndex.value = 0;
  
  if (searchResults.value.length > 0) {
    // Highlight and scroll to the first result
    nextTick(() => {
      highlightAndScrollToResult(searchResultIndex.value);
    });
  }
  
  isSearching.value = false;
}

function highlightAndScrollToResult(index) {
  // Remove any existing highlights
  document.querySelectorAll('.search-highlight').forEach(el => {
    el.classList.remove('search-highlight');
  });
  
  // Highlight and scroll to the current result
  if (searchResults.value[index]) {
    const messageEl = document.getElementById(`message-${searchResults.value[index].id}`);
    if (messageEl) {
      messageEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      messageEl.classList.add('search-highlight');
    }
  }
}

function clearMessageSearch() {
  messageSearchQuery.value = '';
  searchResults.value = [];
  // Remove any highlights
  document.querySelectorAll('.search-highlight').forEach(el => {
    el.classList.remove('search-highlight');
  });
}

// Add these functions for search functionality
function navigateToPreviousResult() {
  if (searchResultIndex.value > 0) {
    searchResultIndex.value--;
    highlightAndScrollToResult(searchResultIndex.value);
  }
}

function navigateToNextResult() {
  if (searchResultIndex.value < searchResults.value.length - 1) {
    searchResultIndex.value++;
    highlightAndScrollToResult(searchResultIndex.value);
  }
}

// Add this function to remove participants from a group
async function removeParticipant(userId) {
  if (!currentConversation.value || !currentConversation.value.group_id) return;
  
  try {
    await axios.post(`/groups/${currentConversation.value.group_id}/remove-users`, {
      user_ids: [userId]
    });
    
    // Update the group members list
    const index = selectedItem.value.users.findIndex(u => u.id === userId);
    if (index !== -1) {
      selectedItem.value.users.splice(index, 1);
    }
    
    // Add a system message
    const removedUser = selectedItem.value.users.find(u => u.id === userId);
    const systemMessage = {
      id: Date.now(),
      user_id: null,
      message: `${removedUser ? removedUser.name : 'User'} has been removed from the group`,
      created_at: new Date().toISOString(),
      is_system: true
    };
    
    conversations.value.push(systemMessage);
    scrollToBottom();
  } catch (e) {
    console.error('Error removing user from group:', e);
  }
}

function handleKeyDown(e) {
  // If Enter is pressed without Shift, send the message
  console.log(e.shiftKey);
  console.log(e.key);
  console.log('1234');
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
  // If Shift+Enter is pressed, insert a line break
  else if (e.key === 'Alt' && e.shiftKey) {
    e.preventDefault();
    
    // Insert a line break at cursor position
    // Use insertHTML to ensure it's saved as an HTML <br> tag
    document.execCommand('insertHTML', false, '<br>');
    
    // Update the message content with the raw HTML content
    messageContent.value = messageInput.value.innerHTML;
    
    // Log the content for debugging
    console.log('Message content after line break:', messageContent.value);
  }
}
</script>

<style scoped>
.chat-container {
  height: 90vh;
  min-height: 600px;
  border-radius: 0;
  overflow: hidden;
  box-shadow: 0 4px 12px 0 rgba(0,0,0,0.1);
  background: #181F2A;
  border: none;
  transition: all 0.3s ease;
}

::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-thumb {
  background: #232B3E;
  border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
  background: #FFA726;
}
.animate-fade-in-up {
  animation: fade-in-up 0.4s;
}
@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(40px);}
  to { opacity: 1; transform: translateY(0);}
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

/* Message highlight animation */
@keyframes highlight-pulse {
  0% { background-color: rgba(255, 165, 0, 0.1); }
  50% { background-color: rgba(255, 165, 0, 0.2); }
  100% { background-color: rgba(255, 165, 0, 0); }
}

.highlight {
  animation: highlight-pulse 2s ease-in-out;
  border-radius: 1rem;
}

/* Search highlight animation */
@keyframes search-highlight-pulse {
  0% { background-color: rgba(255, 0, 0, 0.1); }
  50% { background-color: rgba(255, 0, 0, 0.2); }
  100% { background-color: rgba(255, 0, 0, 0.1); }
}

.search-highlight {
  animation: search-highlight-pulse 2s ease-in-out infinite;
  border-radius: 1rem;
}

/* Typing indicator */
.typing-indicator {
  display: flex;
  align-items: center;
}

.typing-indicator span {
  height: 8px;
  width: 8px;
  margin: 0 1px;
  background-color: #FFA726;
  display: block;
  border-radius: 50%;
  opacity: 0.4;
}

.typing-indicator span:nth-of-type(1) {
  animation: typing 1s infinite;
}

.typing-indicator span:nth-of-type(2) {
  animation: typing 1s 0.33s infinite;
}

.typing-indicator span:nth-of-type(3) {
  animation: typing 1s 0.66s infinite;
}

@keyframes typing {
  0%, 100% {
    transform: translateY(0);
    opacity: 0.4;
  }
  
  50% {
    transform: translateY(-5px);
    opacity: 1;
  }
}

/* Badge animation */
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.animate-pulse {
  animation: pulse 1.5s infinite;
}

/* Contenteditable placeholder */
[contenteditable=true]:empty:before {
  content: attr(placeholder);
  color: #6B7280;
  pointer-events: none;
  display: block;
}

/* Contenteditable styling */
[contenteditable=true] {
  white-space: pre-wrap;
  word-break: break-word;
  min-height: 2.5rem;
  max-height: 8rem;
  overflow-y: auto;
  line-height: 1.5;
}

[contenteditable=true] br {
  display: block;
  content: "";
  margin-top: 0.5em;
}

[contenteditable=true]:focus {
  outline: none;
}

/* HTML message styling */
:deep(.html-message) {
  max-width: 100%;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-word;
}

:deep(.html-message br) {
  display: block;
  content: "";
  margin-top: 0.5em;
}

:deep(.html-message p) {
  margin-bottom: 0.5em;
}

:deep(.html-message img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  margin: 0.5rem 0;
}

:deep(.html-message a) {
  color: #FFA726;
  text-decoration: underline;
}

:deep(.html-message table) {
  border-collapse: collapse;
  margin: 0.5rem 0;
}

:deep(.html-message table td, .html-message table th) {
  border: 1px solid #2A3446;
  padding: 0.25rem 0.5rem;
}

:deep(.html-message pre, .html-message code) {
  background-color: #0A1526;
  border-radius: 0.25rem;
  padding: 0.25rem;
  font-family: monospace;
}

/* Notification animation */
.notification-enter-active,
.notification-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}

.notification-enter-from,
.notification-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* Ensure the notification has proper z-index */
.notification {
  z-index: 9999;
}
</style> 