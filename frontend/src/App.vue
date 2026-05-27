<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { api, clearToken, queryString, setToken } from './services/api'

const user = ref(null)
const tasks = ref([])
const dashboard = ref({
  total_tasks: 0,
  completed_tasks: 0,
  pending_tasks: 0,
  high_priority_tasks: 0,
})
const selectedTask = ref(null)
const aiSummary = ref(null)
const mode = ref('list')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const fieldErrors = ref({})

const loginForm = reactive({
  email: 'admin@example.com',
  password: 'password',
})

const filters = reactive({
  search: '',
  status: '',
  priority: '',
})

const taskForm = reactive({
  id: null,
  title: '',
  description: '',
  priority: 'medium',
  status: 'pending',
  due_date: '',
  assigned_to: 2,
})

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'In progress', value: 'in_progress' },
  { label: 'Completed', value: 'completed' },
]

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
]

const completionRate = computed(() => {
  if (!dashboard.value.total_tasks) {
    return 0
  }

  return Math.round((dashboard.value.completed_tasks / dashboard.value.total_tasks) * 100)
})

const isAdmin = computed(() => user.value?.role === 'admin')

function priorityClass(priority) {
  return {
    low: 'bg-emerald-50 text-emerald-700',
    medium: 'bg-amber-50 text-amber-700',
    high: 'bg-rose-50 text-rose-700',
  }[priority] || 'bg-slate-100 text-slate-600'
}

function statusClass(status) {
  return {
    pending: 'bg-slate-100 text-slate-700',
    in_progress: 'bg-cyan-50 text-cyan-700',
    completed: 'bg-emerald-50 text-emerald-700',
  }[status] || 'bg-slate-100 text-slate-600'
}

function resetErrors() {
  error.value = ''
  fieldErrors.value = {}
}

function handleApiError(exception) {
  error.value = exception.message
  fieldErrors.value = exception.errors || {}

  if (exception.status === 401) {
    clearToken()
    user.value = null
  }
}

async function login() {
  resetErrors()
  loading.value = true

  try {
    const payload = await api('/login', {
      method: 'POST',
      body: loginForm,
    })
    setToken(payload.token)
    user.value = payload.user
    await loadWorkspace()
  } catch (exception) {
    handleApiError(exception)
  } finally {
    loading.value = false
  }
}

async function logout() {
  try {
    await api('/logout', { method: 'POST' })
  } catch {
    // Token may already be expired; local cleanup still applies.
  }

  clearToken()
  user.value = null
  tasks.value = []
  selectedTask.value = null
  mode.value = 'list'
}

async function fetchCurrentUser() {
  const payload = await api('/user')
  user.value = payload
}

async function loadWorkspace() {
  await Promise.all([loadDashboard(), loadTasks()])
}

async function loadDashboard() {
  const payload = await api(`/dashboard${queryString(filters)}`)
  dashboard.value = payload.data
}

async function loadTasks() {
  const payload = await api(`/tasks${queryString(filters)}`)
  tasks.value = payload.data
}

async function applyFilters() {
  loading.value = true
  resetErrors()

  try {
    await loadWorkspace()
  } catch (exception) {
    handleApiError(exception)
  } finally {
    loading.value = false
  }
}

function clearFilters() {
  filters.search = ''
  filters.status = ''
  filters.priority = ''
  applyFilters()
}

function startCreate() {
  selectedTask.value = null
  aiSummary.value = null
  Object.assign(taskForm, {
    id: null,
    title: '',
    description: '',
    priority: 'medium',
    status: 'pending',
    due_date: '',
    assigned_to: user.value?.id || 2,
  })
  resetErrors()
  mode.value = 'form'
}

function startEdit(task) {
  selectedTask.value = task
  aiSummary.value = null
  Object.assign(taskForm, {
    id: task.id,
    title: task.title,
    description: task.description || '',
    priority: task.priority,
    status: task.status,
    due_date: task.due_date || '',
    assigned_to: task.assigned_to,
  })
  resetErrors()
  mode.value = 'form'
}

async function saveTask() {
  saving.value = true
  resetErrors()

  const body = {
    title: taskForm.title,
    description: taskForm.description,
    priority: taskForm.priority,
    status: taskForm.status,
    due_date: taskForm.due_date || null,
    assigned_to: Number(taskForm.assigned_to),
  }

  try {
    const payload = taskForm.id
      ? await api(`/tasks/${taskForm.id}`, { method: 'PATCH', body })
      : await api('/tasks', { method: 'POST', body })

    selectedTask.value = payload.data
    await loadWorkspace()
    mode.value = 'detail'
  } catch (exception) {
    handleApiError(exception)
  } finally {
    saving.value = false
  }
}

async function openDetail(task) {
  resetErrors()
  aiSummary.value = null

  try {
    const payload = await api(`/tasks/${task.id}`)
    selectedTask.value = payload.data
    mode.value = 'detail'
  } catch (exception) {
    handleApiError(exception)
  }
}

async function updateStatus(task, status) {
  resetErrors()

  try {
    const payload = await api(`/tasks/${task.id}/status`, {
      method: 'PATCH',
      body: { status },
    })
    selectedTask.value = payload.data
    await loadWorkspace()
  } catch (exception) {
    handleApiError(exception)
  }
}

async function loadAiSummary() {
  if (!selectedTask.value) {
    return
  }

  loading.value = true
  resetErrors()

  try {
    const payload = await api(`/tasks/${selectedTask.value.id}/ai-summary`)
    aiSummary.value = payload.data
  } catch (exception) {
    handleApiError(exception)
  } finally {
    loading.value = false
  }
}

async function deleteTask(task) {
  if (!window.confirm(`Delete "${task.title}"?`)) {
    return
  }

  resetErrors()

  try {
    await api(`/tasks/${task.id}`, { method: 'DELETE' })
    selectedTask.value = null
    await loadWorkspace()
    mode.value = 'list'
  } catch (exception) {
    handleApiError(exception)
  }
}

onMounted(async () => {
  resetErrors()

  try {
    await fetchCurrentUser()
    await loadWorkspace()
  } catch {
    clearToken()
  }
})
</script>

<template>
  <main class="min-h-screen bg-mist">
    <section v-if="!user" class="mx-auto flex min-h-screen max-w-md items-center px-4 py-10">
      <div class="panel w-full p-6">
        <div class="mb-6">
          <p class="text-sm font-semibold text-brand">AI Task Manager</p>
          <h1 class="mt-2 text-2xl font-bold text-ink">Sign in to manage work</h1>
        </div>

        <form class="space-y-4" @submit.prevent="login">
          <div>
            <label for="email">Email</label>
            <input id="email" v-model="loginForm.email" type="email" autocomplete="email" />
            <p v-if="fieldErrors.email" class="mt-1 text-xs text-rose-600">{{ fieldErrors.email[0] }}</p>
          </div>

          <div>
            <label for="password">Password</label>
            <input id="password" v-model="loginForm.password" type="password" autocomplete="current-password" />
            <p v-if="fieldErrors.password" class="mt-1 text-xs text-rose-600">{{ fieldErrors.password[0] }}</p>
          </div>

          <p v-if="error" class="rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

          <button class="btn btn-primary w-full" type="submit" :disabled="loading">
            {{ loading ? 'Signing in' : 'Sign in' }}
          </button>
        </form>
      </div>
    </section>

    <section v-else>
      <header class="border-b border-line bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-xl font-bold text-ink">AI Task Manager</h1>
            <p class="text-sm text-slate-500">{{ user.name }} · {{ user.role }}</p>
          </div>

          <div class="flex flex-wrap gap-2">
            <button class="btn btn-muted" type="button" @click="mode = 'list'">Task List</button>
            <button class="btn btn-primary" type="button" :disabled="!isAdmin" @click="startCreate">New Task</button>
            <button class="btn btn-muted" type="button" @click="logout">Logout</button>
          </div>
        </div>
      </header>

      <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 lg:grid-cols-[320px_1fr]">
        <aside class="space-y-6">
          <div class="panel p-4">
            <h2 class="text-sm font-bold text-ink">Dashboard</h2>
            <div class="mt-4 grid grid-cols-2 gap-3">
              <div class="rounded-md bg-slate-50 p-3">
                <p class="text-xs text-slate-500">Total</p>
                <p class="text-2xl font-bold">{{ dashboard.total_tasks }}</p>
              </div>
              <div class="rounded-md bg-emerald-50 p-3">
                <p class="text-xs text-emerald-700">Completed</p>
                <p class="text-2xl font-bold text-emerald-800">{{ dashboard.completed_tasks }}</p>
              </div>
              <div class="rounded-md bg-amber-50 p-3">
                <p class="text-xs text-amber-700">Pending</p>
                <p class="text-2xl font-bold text-amber-800">{{ dashboard.pending_tasks }}</p>
              </div>
              <div class="rounded-md bg-rose-50 p-3">
                <p class="text-xs text-rose-700">High</p>
                <p class="text-2xl font-bold text-rose-800">{{ dashboard.high_priority_tasks }}</p>
              </div>
            </div>

            <div class="mt-4">
              <div class="mb-2 flex justify-between text-xs font-semibold text-slate-500">
                <span>Completion</span>
                <span>{{ completionRate }}%</span>
              </div>
              <progress class="h-2 w-full overflow-hidden rounded-full accent-brand" :value="completionRate" max="100"></progress>
            </div>
          </div>

          <div class="panel p-4">
            <h2 class="text-sm font-bold text-ink">Filters</h2>
            <div class="mt-4 space-y-3">
              <div>
                <label for="search">Search</label>
                <input id="search" v-model="filters.search" type="search" placeholder="Title or description" />
              </div>
              <div>
                <label for="status">Status</label>
                <select id="status" v-model="filters.status">
                  <option value="">All statuses</option>
                  <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>
              <div>
                <label for="priority">Priority</label>
                <select id="priority" v-model="filters.priority">
                  <option value="">All priorities</option>
                  <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>
              <div class="flex gap-2">
                <button class="btn btn-primary flex-1" type="button" @click="applyFilters">Apply</button>
                <button class="btn btn-muted flex-1" type="button" @click="clearFilters">Clear</button>
              </div>
            </div>
          </div>
        </aside>

        <section class="min-w-0">
          <p v-if="error" class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</p>

          <div v-if="mode === 'list'" class="panel overflow-hidden">
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
              <h2 class="text-sm font-bold">Tasks</h2>
              <span class="text-sm text-slate-500">{{ tasks.length }} shown</span>
            </div>

            <div v-if="tasks.length" class="divide-y divide-line">
              <article v-for="task in tasks" :key="task.id" class="grid gap-3 px-4 py-4 md:grid-cols-[1fr_auto]">
                <button class="text-left" type="button" @click="openDetail(task)">
                  <h3 class="font-semibold text-ink">{{ task.title }}</h3>
                  <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ task.description || 'No description' }}</p>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <span class="badge" :class="priorityClass(task.priority)">{{ task.priority }}</span>
                    <span class="badge" :class="statusClass(task.status)">{{ task.status.replace('_', ' ') }}</span>
                    <span v-if="task.due_date" class="badge bg-slate-100 text-slate-700">{{ task.due_date }}</span>
                  </div>
                </button>

                <div class="flex items-center gap-2 md:justify-end">
                  <select class="h-10 min-w-36" :value="task.status" @change="updateStatus(task, $event.target.value)">
                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                  <button v-if="isAdmin" class="btn btn-muted" type="button" @click="startEdit(task)">Edit</button>
                </div>
              </article>
            </div>

            <div v-else class="px-4 py-12 text-center text-sm text-slate-500">
              No tasks found.
            </div>
          </div>

          <div v-if="mode === 'form'" class="panel p-4 sm:p-6">
            <div class="mb-5 flex items-center justify-between">
              <h2 class="text-lg font-bold">{{ taskForm.id ? 'Edit Task' : 'Create Task' }}</h2>
              <button class="btn btn-muted" type="button" @click="mode = 'list'">Back</button>
            </div>

            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="saveTask">
              <div class="md:col-span-2">
                <label for="title">Title</label>
                <input id="title" v-model="taskForm.title" />
                <p v-if="fieldErrors.title" class="mt-1 text-xs text-rose-600">{{ fieldErrors.title[0] }}</p>
              </div>
              <div class="md:col-span-2">
                <label for="description">Description</label>
                <textarea id="description" v-model="taskForm.description" rows="5"></textarea>
              </div>
              <div>
                <label for="form-priority">Priority</label>
                <select id="form-priority" v-model="taskForm.priority">
                  <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>
              <div>
                <label for="form-status">Status</label>
                <select id="form-status" v-model="taskForm.status">
                  <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>
              <div>
                <label for="due-date">Due Date</label>
                <input id="due-date" v-model="taskForm.due_date" type="date" />
              </div>
              <div>
                <label for="assigned-to">Assigned User ID</label>
                <input id="assigned-to" v-model="taskForm.assigned_to" type="number" min="1" />
              </div>
              <div class="flex gap-2 md:col-span-2">
                <button class="btn btn-primary" type="submit" :disabled="saving">
                  {{ saving ? 'Saving' : 'Save Task' }}
                </button>
                <button class="btn btn-muted" type="button" @click="mode = 'list'">Cancel</button>
              </div>
            </form>
          </div>

          <div v-if="mode === 'detail' && selectedTask" class="space-y-4">
            <div class="panel p-4 sm:p-6">
              <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <h2 class="text-xl font-bold">{{ selectedTask.title }}</h2>
                  <p class="mt-2 text-sm leading-6 text-slate-600">{{ selectedTask.description || 'No description' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                  <button v-if="isAdmin" class="btn btn-muted" type="button" @click="startEdit(selectedTask)">Edit</button>
                  <button v-if="isAdmin" class="btn btn-danger" type="button" @click="deleteTask(selectedTask)">Delete</button>
                  <button class="btn btn-muted" type="button" @click="mode = 'list'">Back</button>
                </div>
              </div>

              <dl class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-md bg-slate-50 p-3">
                  <dt class="text-xs text-slate-500">Priority</dt>
                  <dd class="mt-1"><span class="badge" :class="priorityClass(selectedTask.priority)">{{ selectedTask.priority }}</span></dd>
                </div>
                <div class="rounded-md bg-slate-50 p-3">
                  <dt class="text-xs text-slate-500">Status</dt>
                  <dd class="mt-1"><span class="badge" :class="statusClass(selectedTask.status)">{{ selectedTask.status.replace('_', ' ') }}</span></dd>
                </div>
                <div class="rounded-md bg-slate-50 p-3">
                  <dt class="text-xs text-slate-500">Due</dt>
                  <dd class="mt-2 text-sm font-semibold">{{ selectedTask.due_date || 'Not set' }}</dd>
                </div>
                <div class="rounded-md bg-slate-50 p-3">
                  <dt class="text-xs text-slate-500">Assigned</dt>
                  <dd class="mt-2 text-sm font-semibold">{{ selectedTask.user?.name || selectedTask.assigned_to }}</dd>
                </div>
              </dl>
            </div>

            <div class="panel p-4 sm:p-6">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-bold">AI Summary</h2>
                <button class="btn btn-primary" type="button" :disabled="loading" @click="loadAiSummary">
                  {{ loading ? 'Generating' : 'Refresh AI Summary' }}
                </button>
              </div>

              <div class="mt-4 rounded-md bg-slate-50 p-4">
                <p class="text-sm leading-6 text-slate-700">
                  {{ aiSummary?.ai_summary || selectedTask.ai_summary || 'No AI summary generated yet.' }}
                </p>
                <p class="mt-3">
                  <span class="badge" :class="priorityClass(aiSummary?.ai_priority || selectedTask.ai_priority)">
                    AI priority: {{ aiSummary?.ai_priority || selectedTask.ai_priority || 'unknown' }}
                  </span>
                </p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </section>
  </main>
</template>
