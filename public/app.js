// ── Константы ────────────────────────────────────────────────────────────────
const STORAGE_KEY = 'auth_token';
const API_LOGIN_URL = '/api/auth/token';

// ── Утилиты ──────────────────────────────────────────────────────────────────

/**
 * Декодирует JWT-payload без проверки подписи.
 * Возвращает null если токен битый, невалидный или просрочен.
 */
function decodeJwtPayload(token) {
    try {
        const parts = token.split('.');
        if (parts.length !== 3) return null;
        // base64url → base64
        let base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
        while (base64.length % 4 !== 0) base64 += '=';
        const payload = JSON.parse(atob(base64));
        // Проверяем срок действия
        if (!payload.exp || payload.exp <= Math.floor(Date.now() / 1000)) return null;
        return payload;
    } catch (e) {
        return null;
    }
}

// ── Реактивное состояние авторизации ─────────────────────────────────────────

const authState = Vue.reactive({ token: null, userId: null, userUuid: null, roles: [] });

function writeAuthFromToken(token) {
    const payload = decodeJwtPayload(token);
    if (payload) {
        authState.token = token;
        authState.userId = payload.username;
        authState.userUuid = payload.id || null;
        authState.roles = payload.roles || [];
        try {
            localStorage.setItem(STORAGE_KEY, token);
        } catch (e) {
            console.error('[SPA]', e);
        }
    } else {
        clearAuth();
    }
}

function clearAuth() {
    authState.token = null;
    authState.userId = null;
    authState.userUuid = null;
    authState.roles = [];
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch (e) {
        console.error('[SPA]', e);
    }
}

// ── axios-инстанс ─────────────────────────────────────────────────────────────

const api = axios.create({ baseURL: '' });

// Request interceptor: добавляем Bearer-токен если авторизованы (US2)
api.interceptors.request.use(config => {
    if (authState.token) {
        config.headers.Authorization = 'Bearer ' + authState.token;
    }
    return config;
});

// Response interceptor: глобальный 401 → выход (кроме самого эндпоинта логина) (US2)
api.interceptors.response.use(
    r => r,
    err => {
        const url = err.config?.url || '';
        if (err.response?.status === 401 && !url.endsWith(API_LOGIN_URL)) {
            clearAuth();
            window.location.hash = '#/login';
        }
        return Promise.reject(err);
    }
);

// ── Глобальные кэши модулей и занятий ────────────────────────────────────────

let _modulesCache = null;
let _lessonsCache = null;
let _modulesPromise = null;
let _lessonsPromise = null;

function clearGlobalCaches() {
    _modulesCache = null;
    _lessonsCache = null;
    _modulesPromise = null;
    _lessonsPromise = null;
}

async function fetchAllModules() {
    if (_modulesCache) return _modulesCache;
    if (!_modulesPromise) {
        _modulesPromise = api.get('/api/v1/modules').then(r => {
            const byCourseId = {};
            for (const m of r.data) {
                if (!byCourseId[m.courseId]) byCourseId[m.courseId] = [];
                byCourseId[m.courseId].push(m);
            }
            _modulesCache = { byCourseId };
            return _modulesCache;
        }).finally(() => { _modulesPromise = null; });
    }
    return _modulesPromise;
}

async function fetchAllLessons() {
    if (_lessonsCache) return _lessonsCache;
    if (!_lessonsPromise) {
        _lessonsPromise = api.get('/api/v1/lessons').then(r => {
            const byModuleId = {};
            for (const l of r.data) {
                if (!byModuleId[l.moduleId]) byModuleId[l.moduleId] = [];
                byModuleId[l.moduleId].push(l);
            }
            _lessonsCache = { byModuleId };
            return _lessonsCache;
        }).finally(() => { _lessonsPromise = null; });
    }
    return _lessonsPromise;
}

// ── Форматирование баллов ─────────────────────────────────────────────────────

function formatScore(value) {
    if (value === undefined || value === null) return '…';
    return value.toFixed(1) + ' баллов';
}

// ── Hash-роутер ───────────────────────────────────────────────────────────────

const SUPPORTED_ROUTES = ['login', 'courses', 'score-form'];
const currentQuery = Vue.reactive({});

function parseHash() {
    const hash = window.location.hash;
    const match = hash.match(/^#\/([^?]+)(\?(.*))?$/);
    const name = match ? match[1] : null;
    const qs = (match && match[3]) ? match[3] : '';
    for (const k of Object.keys(currentQuery)) delete currentQuery[k];
    if (qs) {
        for (const part of qs.split('&')) {
            const idx = part.indexOf('=');
            if (idx > 0) {
                const k = decodeURIComponent(part.slice(0, idx));
                const v = decodeURIComponent(part.slice(idx + 1));
                currentQuery[k] = v;
            }
        }
    }
    return SUPPORTED_ROUTES.includes(name) ? name : null;
}

const currentRoute = Vue.ref(parseHash());

window.addEventListener('hashchange', () => {
    currentRoute.value = parseHash();
    applyRouteGuard(); // US2
});

function applyRouteGuard() {
    let target;
    if (!authState.token && currentRoute.value !== 'login') {
        target = 'login';
    } else if (authState.token && (currentRoute.value === 'login' || currentRoute.value === null)) {
        target = 'courses';
    } else {
        target = currentRoute.value;
    }
    if (target === 'score-form') {
        const roles = authState.roles;
        if (!roles.includes('ROLE_ADMIN') && !roles.includes('ROLE_TEACHER')) {
            target = 'courses';
        }
    }
    if (target !== currentRoute.value) {
        window.location.hash = '#/' + target;
    }
}

// ── Восстановление состояния из localStorage ──────────────────────────────────

function loadAuthFromStorage() {
    let raw = null;
    try {
        raw = localStorage.getItem(STORAGE_KEY);
    } catch (e) {
        console.error('[SPA]', e);
        return;
    }
    if (raw === null) return;
    const payload = decodeJwtPayload(raw);
    if (!payload || !payload.id) {
        clearAuth();
    } else {
        authState.token = raw;
        authState.userId = payload.username;
        authState.userUuid = payload.id;
        authState.roles = payload.roles || [];
    }
}

// ── Компоненты ────────────────────────────────────────────────────────────────

const LoginPage = {
    setup() {
        const state = Vue.reactive({
            email: '',
            password: '',
            isSubmitting: false,
            fieldErrors: {},
            formError: null,
        });

        function validateForm() {
            state.fieldErrors = {};
            if (!state.email) state.fieldErrors.email = 'Поле обязательно';
            if (!state.password) state.fieldErrors.password = 'Поле обязательно';
            return Object.keys(state.fieldErrors).length === 0;
        }

        async function onSubmit() {
            state.fieldErrors = {};
            state.formError = null;
            if (!validateForm()) return;
            state.isSubmitting = true;
            try {
                const response = await api.post(API_LOGIN_URL, {
                    email: state.email,
                    password: state.password,
                });
                writeAuthFromToken(response.data.token);
                window.location.hash = '#/courses';
            } catch (err) {
                if (err.response?.status === 401) {
                    state.formError = 'Неверный email или пароль';
                } else {
                    state.formError = 'Ошибка входа, попробуйте позже';
                    console.error('[SPA]', err);
                }
            } finally {
                state.isSubmitting = false;
            }
        }

        return { state, onSubmit };
    },
    template: `
        <div class="login-card">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">Вход</h4>
                    <form @submit.prevent="onSubmit">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                v-model="state.email"
                                placeholder="email@example.com"
                                :disabled="state.isSubmitting"
                            >
                            <div class="invalid-feedback d-block" v-if="state.fieldErrors.email">
                                {{ state.fieldErrors.email }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input
                                type="password"
                                class="form-control"
                                v-model="state.password"
                                :disabled="state.isSubmitting"
                            >
                            <div class="invalid-feedback d-block" v-if="state.fieldErrors.password">
                                {{ state.fieldErrors.password }}
                            </div>
                        </div>
                        <div class="alert alert-danger" v-if="state.formError">
                            {{ state.formError }}
                        </div>
                        <button type="submit" class="btn btn-primary w-100" :disabled="state.isSubmitting">
                            <span v-if="state.isSubmitting">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Вход…
                            </span>
                            <span v-else>Войти</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    `,
};

const LessonRow = {
    props: ['lesson', 'isOpen', 'skillScores', 'skillScoresLoading', 'skillScoresError', 'canGrade', 'gradingStudentId'],
    emits: ['toggle', 'retry-skills', 'grade'],
    setup() {
        return { formatScore };
    },
    template: `
        <div>
            <div class="tree-level-2 tree-row d-flex align-items-center gap-2 py-1"
                 @click="$emit('toggle')">
                <span>{{ isOpen ? '▼' : '▶' }}</span>
                <span>{{ lesson.title }}</span>
                <button v-if="canGrade" type="button" class="btn btn-sm btn-outline-primary ms-auto"
                        @click.stop="$emit('grade', lesson.id)">Выставить оценку</button>
            </div>
            <div v-show="isOpen" class="tree-collapse">
                <div v-if="skillScoresLoading" class="py-2 ps-5">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>
                <div v-else-if="skillScoresError"
                     class="alert alert-danger d-flex align-items-center gap-2 py-2 my-1 ms-5">
                    {{ skillScoresError }}
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            @click.stop="$emit('retry-skills')">Повторить</button>
                </div>
                <div v-else-if="Array.isArray(skillScores) && skillScores.length === 0"
                     class="text-muted py-2 ps-5">
                    Баллы за это занятие пока не выставлены
                </div>
                <table v-else-if="Array.isArray(skillScores) && skillScores.length > 0"
                       class="table table-sm my-1 ms-5" style="max-width: 400px;">
                    <thead>
                        <tr>
                            <th>Навык</th>
                            <th>Балл</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ss in skillScores" :key="ss.skillId">
                            <td>{{ ss.skillTitle }}</td>
                            <td>{{ formatScore(ss.totalScore) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `,
};

// T012, T020: добавлен prop moduleScoreError, обновлён бейдж (3-state: спиннер / ошибка доступа / балл)
const ModuleRow = {
    props: [
        'module', 'score', 'moduleScoreError', 'isOpen',
        'lessons', 'lessonsLoading', 'lessonsError',
        'expandedLessons', 'skillScoresByLessonId', 'skillScoresLoading', 'skillScoresError',
        'canGrade', 'gradingStudentId',
    ],
    emits: ['toggle', 'retry-lessons', 'toggle-lesson', 'retry-skills'],
    setup(props) {
        function goToScoreForm(lessonId) {
            window.location.hash = '#/score-form?lessonId=' + lessonId + '&studentId=' + props.gradingStudentId;
        }
        return { formatScore, goToScoreForm };
    },
    template: `
        <div>
            <div class="tree-level-1 tree-row d-flex align-items-center gap-2 py-1"
                 @click="$emit('toggle')">
                <span>{{ isOpen ? '▼' : '▶' }}</span>
                <span>{{ module.title }}</span>
                <span v-if="score === undefined && !moduleScoreError"
                      class="spinner-border spinner-border-sm" role="status"></span>
                <span v-else-if="moduleScoreError"
                      class="badge bg-danger">{{ moduleScoreError }}</span>
                <span v-else
                      class="badge bg-info text-dark">Итого: {{ formatScore(score) }}</span>
            </div>
            <div v-show="isOpen" class="tree-collapse">
                <div v-if="lessonsLoading" class="py-2 ps-4">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>
                <div v-else-if="lessonsError"
                     class="alert alert-danger d-flex align-items-center gap-2 py-2 my-1 ms-4">
                    {{ lessonsError }}
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            @click.stop="$emit('retry-lessons')">Повторить</button>
                </div>
                <div v-else-if="lessons && lessons.length === 0"
                     class="text-muted py-2 ps-4">Нет занятий</div>
                <div v-else-if="lessons && lessons.length > 0">
                    <lesson-row
                        v-for="lesson in lessons"
                        :key="lesson.id"
                        :lesson="lesson"
                        :is-open="!!expandedLessons[lesson.id]"
                        :skill-scores="skillScoresByLessonId[lesson.id]"
                        :skill-scores-loading="!!skillScoresLoading[lesson.id]"
                        :skill-scores-error="skillScoresError[lesson.id] || null"
                        :can-grade="canGrade"
                        :grading-student-id="gradingStudentId"
                        @toggle="$emit('toggle-lesson', lesson.id)"
                        @retry-skills="$emit('retry-skills', lesson.id)"
                        @grade="goToScoreForm"
                    />
                </div>
            </div>
        </div>
    `,
};

// T011, T019: добавлены props courseScoreError и moduleScoresError
// бейдж курса — 3-state; module-row получает moduleScoreError по id модуля
const CourseRow = {
    props: [
        'course', 'score', 'courseScoreError', 'isOpen',
        'modules', 'modulesLoading', 'modulesError',
        'moduleScores', 'moduleScoresError', 'expandedModules',
        'lessonsByModuleId', 'lessonsLoading', 'lessonsError',
        'expandedLessons', 'skillScoresByLessonId', 'skillScoresLoading', 'skillScoresError',
        'canGrade', 'gradingStudentId',
    ],
    emits: ['toggle', 'retry-modules', 'toggle-module', 'retry-lessons', 'toggle-lesson', 'retry-skills'],
    setup() {
        return { formatScore };
    },
    template: `
        <div class="border-bottom">
            <div class="tree-row d-flex align-items-center gap-2 py-2"
                 @click="$emit('toggle')">
                <span>{{ isOpen ? '▼' : '▶' }}</span>
                <span class="fw-semibold">{{ course.title }}</span>
                <span v-if="score === undefined && !courseScoreError"
                      class="spinner-border spinner-border-sm" role="status"></span>
                <span v-else-if="courseScoreError"
                      class="badge bg-danger">{{ courseScoreError }}</span>
                <span v-else
                      class="badge bg-secondary">Итого: {{ formatScore(score) }}</span>
            </div>
            <div v-show="isOpen" class="tree-collapse">
                <div v-if="modulesLoading" class="py-2 ps-3">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>
                <div v-else-if="modulesError"
                     class="alert alert-danger d-flex align-items-center gap-2 py-2 my-1 ms-3">
                    {{ modulesError }}
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            @click.stop="$emit('retry-modules')">Повторить</button>
                </div>
                <div v-else-if="modules && modules.length === 0"
                     class="text-muted py-2 ps-3">Нет модулей</div>
                <div v-else-if="modules && modules.length > 0">
                    <module-row
                        v-for="module in modules"
                        :key="module.id"
                        :module="module"
                        :score="moduleScores[module.id]"
                        :module-score-error="moduleScoresError && moduleScoresError[module.id] || null"
                        :is-open="!!expandedModules[module.id]"
                        :lessons="lessonsByModuleId[module.id] || []"
                        :lessons-loading="!!lessonsLoading[module.id]"
                        :lessons-error="lessonsError[module.id] || null"
                        :expanded-lessons="expandedLessons"
                        :skill-scores-by-lesson-id="skillScoresByLessonId"
                        :skill-scores-loading="skillScoresLoading"
                        :skill-scores-error="skillScoresError"
                        :can-grade="canGrade"
                        :grading-student-id="gradingStudentId"
                        @toggle="$emit('toggle-module', module.id)"
                        @retry-lessons="$emit('retry-lessons', module.id)"
                        @toggle-lesson="$emit('toggle-lesson', $event)"
                        @retry-skills="$emit('retry-skills', $event)"
                    />
                </div>
            </div>
        </div>
    `,
};

const CoursesPage = {
    setup() {
        // T002, T007: новые поля state
        const state = Vue.reactive({
            courses: [],
            courseScores: {},
            coursesLoading: false,
            coursesError: null,
            expandedCourses: {},
            modulesByCourseId: {},
            modulesLoading: {},
            modulesError: {},
            moduleScores: {},
            expandedModules: {},
            lessonsByModuleId: {},
            lessonsLoading: {},
            lessonsError: {},
            expandedLessons: {},
            skillScoresByLessonId: {},
            skillScoresLoading: {},
            skillScoresError: {},
            // T002: viewedUserId и счётчик версий для защиты от race condition
            // Если в hash передан studentId (после сохранения оценки) и роль admin/teacher —
            // открываем дерево с уже выбранным студентом.
            viewedUserId: (
                currentQuery.studentId &&
                (authState.roles.includes('ROLE_ADMIN') || authState.roles.includes('ROLE_TEACHER'))
            ) ? currentQuery.studentId : authState.userUuid,
            scoreVersion: 0,
            // T007: список пользователей для селектора
            usersList: [],
            usersLoading: false,
            usersError: null,
            // T007: ошибки доступа 403 per-entity
            courseScoresError: {},
            moduleScoresError: {},
        });

        // T003, T016: используем viewedUserId, version guard, обработка 403
        async function loadAllCourseScores() {
            const ver = state.scoreVersion;
            await Promise.all(state.courses.map(course =>
                api.get(`/api/v1/users/${state.viewedUserId}/courses/${course.id}/score`)
                    .then(r => {
                        if (ver !== state.scoreVersion) return;
                        state.courseScores[course.id] = r.data.totalScore;
                    })
                    .catch(err => {
                        if (ver !== state.scoreVersion) return;
                        if (err.response?.status === 403) {
                            state.courseScoresError[course.id] = 'Нет доступа';
                        } else {
                            state.courseScores[course.id] = 0;
                        }
                    })
            ));
        }

        async function loadCourses() {
            state.coursesLoading = true;
            state.coursesError = null;
            try {
                const r = await api.get('/api/v1/courses');
                state.courses = r.data;
                await loadAllCourseScores();
            } catch (err) {
                state.coursesError = 'Ошибка загрузки курсов';
            } finally {
                state.coursesLoading = false;
            }
        }

        // T004, T017: используем viewedUserId, обработка 403 для баллов модулей
        async function loadModulesForCourse(courseId) {
            state.modulesLoading[courseId] = true;
            state.modulesError[courseId] = null;
            try {
                const cache = await fetchAllModules();
                state.modulesByCourseId[courseId] = cache.byCourseId[courseId] || [];
                const modules = state.modulesByCourseId[courseId];
                await Promise.all(modules.map(m =>
                    api.get(`/api/v1/users/${state.viewedUserId}/modules/${m.id}/score`)
                        .then(r => { state.moduleScores[m.id] = r.data.totalScore; })
                        .catch(err => {
                            if (err.response?.status === 403) {
                                state.moduleScoresError[m.id] = 'Нет доступа';
                            } else {
                                state.moduleScores[m.id] = 0;
                            }
                        })
                ));
            } catch (err) {
                state.modulesError[courseId] = 'Ошибка загрузки модулей';
            } finally {
                state.modulesLoading[courseId] = false;
            }
        }

        async function toggleCourse(courseId) {
            if (Array.isArray(state.modulesByCourseId[courseId])) {
                state.expandedCourses[courseId] = !state.expandedCourses[courseId];
            } else if (!state.modulesLoading[courseId]) {
                state.expandedCourses[courseId] = true;
                await loadModulesForCourse(courseId);
            }
        }

        async function loadLessonsForModule(moduleId) {
            state.lessonsLoading[moduleId] = true;
            state.lessonsError[moduleId] = null;
            try {
                const cache = await fetchAllLessons();
                state.lessonsByModuleId[moduleId] = cache.byModuleId[moduleId] || [];
            } catch (err) {
                state.lessonsError[moduleId] = 'Ошибка загрузки занятий';
            } finally {
                state.lessonsLoading[moduleId] = false;
            }
        }

        async function toggleModule(moduleId) {
            if (Array.isArray(state.lessonsByModuleId[moduleId])) {
                state.expandedModules[moduleId] = !state.expandedModules[moduleId];
            } else if (!state.lessonsLoading[moduleId]) {
                state.expandedModules[moduleId] = true;
                await loadLessonsForModule(moduleId);
            }
        }

        // T005, T018: используем viewedUserId, обработка 403 для навыков
        async function loadSkillScores(lessonId) {
            state.skillScoresLoading[lessonId] = true;
            state.skillScoresError[lessonId] = null;
            try {
                const r = await api.get(`/api/v1/users/${state.viewedUserId}/lessons/${lessonId}/skill-scores`);
                state.skillScoresByLessonId[lessonId] = r.data;
            } catch (err) {
                if (err.response?.status === 404) {
                    state.skillScoresByLessonId[lessonId] = [];
                } else if (err.response?.status === 403) {
                    state.skillScoresError[lessonId] = 'Нет доступа';
                } else {
                    state.skillScoresError[lessonId] = 'Ошибка загрузки баллов';
                }
            } finally {
                state.skillScoresLoading[lessonId] = false;
            }
        }

        async function toggleLesson(lessonId) {
            if (Array.isArray(state.skillScoresByLessonId[lessonId])) {
                state.expandedLessons[lessonId] = !state.expandedLessons[lessonId];
            } else if (!state.skillScoresLoading[lessonId]) {
                state.expandedLessons[lessonId] = true;
                await loadSkillScores(lessonId);
            }
        }

        // T006: перезагрузка баллов модулей для всех уже загруженных курсов
        // с version guard и 403-обработкой (вызывается из onStudentChange)
        function reloadModuleScores() {
            const ver = state.scoreVersion;
            for (const courseId of Object.keys(state.modulesByCourseId)) {
                const modules = state.modulesByCourseId[courseId];
                if (!Array.isArray(modules)) continue;
                for (const m of modules) {
                    api.get(`/api/v1/users/${state.viewedUserId}/modules/${m.id}/score`)
                        .then(r => {
                            if (ver !== state.scoreVersion) return;
                            state.moduleScores[m.id] = r.data.totalScore;
                        })
                        .catch(err => {
                            if (ver !== state.scoreVersion) return;
                            if (err.response?.status === 403) {
                                state.moduleScoresError[m.id] = 'Нет доступа';
                            } else {
                                state.moduleScores[m.id] = 0;
                            }
                        });
                }
            }
        }

        // T006: перезагрузка навыков для раскрытых занятий
        function reloadOpenLessonSkills() {
            for (const lessonId of Object.keys(state.expandedLessons)) {
                if (state.expandedLessons[lessonId] === true) {
                    loadSkillScores(lessonId);
                }
            }
        }

        // T008: загрузка списка всех пользователей для селектора
        async function loadUsers() {
            state.usersLoading = true;
            state.usersError = null;
            try {
                const r = await api.get('/api/v1/users');
                state.usersList = r.data;
            } catch (err) {
                state.usersError = 'Не удалось загрузить список студентов';
            } finally {
                state.usersLoading = false;
            }
        }

        // T009: смена просматриваемого студента
        // role guard + сброс баллов + инкремент версии + перезагрузка
        function onStudentChange(newUserId) {
            if (!authState.roles.includes('ROLE_ADMIN') && !authState.roles.includes('ROLE_TEACHER')) return;
            state.viewedUserId = newUserId;
            state.scoreVersion++;
            // Сброс баллов; {} гарантирует undefined для ключей → активирует спиннеры
            state.courseScores = {};
            state.moduleScores = {};
            state.skillScoresByLessonId = {};
            state.courseScoresError = {};
            state.moduleScoresError = {};
            state.skillScoresError = {};
            state.skillScoresLoading = {};
            // Структура курсов/модулей/занятий и expand-состояния не сбрасываются
            loadAllCourseScores();
            reloadModuleScores();
            reloadOpenLessonSkills();
        }

        function onLogout() {
            clearAuth();
            window.location.hash = '#/login';
        }

        // T010: загружаем пользователей при монтировании только для admin/teacher
        Vue.onMounted(() => {
            loadCourses();
            if (authState.roles.includes('ROLE_ADMIN') || authState.roles.includes('ROLE_TEACHER')) {
                loadUsers();
            }
        });
        Vue.onUnmounted(() => clearGlobalCaches());

        // T014: sortedUsersList — все пользователи кроме текущего, отсортированные по email
        // u.id и authState.userUuid — UUID-строки из одного источника (payload.id / API id)
        const sortedUsersList = Vue.computed(() =>
            state.usersList
                .filter(u => u.id !== authState.userUuid)
                .slice()
                .sort((a, b) => a.email.localeCompare(b.email))
        );

        const canGrade = Vue.computed(() =>
            authState.roles.includes('ROLE_ADMIN') || authState.roles.includes('ROLE_TEACHER')
        );

        return {
            state, authState, formatScore,
            loadCourses, toggleCourse, loadModulesForCourse,
            toggleModule, loadLessonsForModule,
            toggleLesson, loadSkillScores,
            loadUsers, onStudentChange, sortedUsersList,
            canGrade, onLogout,
        };
    },
    template: `
        <div>
            <nav class="navbar navbar-light bg-light px-3">
                <span class="navbar-brand">Прогресс студентов</span>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted">Здравствуйте, {{ authState.userId }}</span>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            @click="onLogout">Выйти</button>
                </div>
            </nav>
            <div class="container py-4">

                <!-- T013: панель селектора — только для ROLE_ADMIN и ROLE_TEACHER (FR-005, FR-014) -->
                <div v-if="authState.roles.includes('ROLE_ADMIN') || authState.roles.includes('ROLE_TEACHER')"
                     class="mb-3 p-3 bg-light border rounded">
                    <div v-if="state.usersError"
                         class="alert alert-warning d-flex align-items-center gap-2 mb-0">
                        {{ state.usersError }}
                        <button type="button" class="btn btn-sm btn-outline-warning ms-2"
                                @click="loadUsers">Повторить</button>
                    </div>
                    <div v-else class="d-flex align-items-center gap-2 flex-wrap">
                        <label class="form-label mb-0 fw-semibold text-nowrap">Просмотр баллов студента:</label>
                        <select class="form-select form-select-sm w-auto"
                                v-model="state.viewedUserId"
                                :disabled="state.usersLoading"
                                @change="onStudentChange(state.viewedUserId)">
                            <option :value="authState.userUuid">Я ({{ authState.userId }})</option>
                            <option v-for="u in sortedUsersList" :key="u.id" :value="u.id">
                                {{ u.email }}
                            </option>
                        </select>
                        <span v-if="state.usersLoading"
                              class="spinner-border spinner-border-sm ms-1" role="status"></span>
                    </div>
                </div>

                <div v-if="state.coursesLoading" class="text-center py-4">
                    <div class="spinner-border" role="status"></div>
                </div>
                <div v-else-if="state.coursesError"
                     class="alert alert-danger d-flex align-items-center gap-2">
                    {{ state.coursesError }}
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            @click="loadCourses">Повторить</button>
                </div>
                <div v-else-if="state.courses.length === 0" class="text-muted">
                    Курсы не найдены
                </div>
                <!-- T021: передаём courseScoreError и moduleScoresError в course-row -->
                <div v-else>
                    <course-row
                        v-for="course in state.courses"
                        :key="course.id"
                        :course="course"
                        :score="state.courseScores[course.id]"
                        :course-score-error="state.courseScoresError[course.id] || null"
                        :is-open="!!state.expandedCourses[course.id]"
                        :modules="state.modulesByCourseId[course.id] || []"
                        :modules-loading="!!state.modulesLoading[course.id]"
                        :modules-error="state.modulesError[course.id] || null"
                        :module-scores="state.moduleScores"
                        :module-scores-error="state.moduleScoresError"
                        :expanded-modules="state.expandedModules"
                        :lessons-by-module-id="state.lessonsByModuleId"
                        :lessons-loading="state.lessonsLoading"
                        :lessons-error="state.lessonsError"
                        :expanded-lessons="state.expandedLessons"
                        :skill-scores-by-lesson-id="state.skillScoresByLessonId"
                        :skill-scores-loading="state.skillScoresLoading"
                        :skill-scores-error="state.skillScoresError"
                        :can-grade="canGrade"
                        :grading-student-id="state.viewedUserId"
                        @toggle="toggleCourse(course.id)"
                        @retry-modules="loadModulesForCourse(course.id)"
                        @toggle-module="toggleModule"
                        @retry-lessons="loadLessonsForModule"
                        @toggle-lesson="toggleLesson"
                        @retry-skills="loadSkillScores"
                    />
                </div>
            </div>
        </div>
    `,
};

const ScoreFormPage = {
    setup() {
        const state = Vue.reactive({
            usersList: [],
            coursesList: [],
            modulesList: [],
            lessonsList: [],
            tasksList: [],
            selectedStudentId: null,
            selectedCourseId: null,
            selectedModuleId: null,
            selectedLessonId: null,
            selectedTaskId: null,
            score: '',
            taskScoreMin: null,
            taskScoreMax: null,
            loadingUsers: false,
            loadingCourses: false,
            loadingTasks: false,
            loadError: null,
            scoreError: null,
            alertMsg: null,
            alertType: null,
            isSaving: false,
            savedSuccessfully: false,
            queryLessonId: null,
            queryStudentId: null,
        });

        async function loadUsers() {
            state.loadingUsers = true;
            try {
                const r = await api.get('/api/v1/users');
                state.usersList = r.data;
            } catch (err) {
                state.loadError = 'Не удалось загрузить список пользователей';
            } finally {
                state.loadingUsers = false;
            }
        }

        async function loadCourses() {
            state.loadingCourses = true;
            try {
                const r = await api.get('/api/v1/courses');
                state.coursesList = r.data;
            } catch (err) {
                state.loadError = 'Не удалось загрузить список курсов';
            } finally {
                state.loadingCourses = false;
            }
        }

        async function onCourseChange(courseId) {
            state.selectedModuleId = null;
            state.selectedLessonId = null;
            state.selectedTaskId = null;
            state.modulesList = [];
            state.lessonsList = [];
            state.tasksList = [];
            state.taskScoreMin = null;
            state.taskScoreMax = null;
            if (!courseId) return;
            const modulesData = await fetchAllModules();
            const modules = modulesData.byCourseId[courseId] || [];
            const lessonsData = await fetchAllLessons();
            const allLessons = Object.values(lessonsData.byModuleId).flat();
            const hasNoModuleLessons = allLessons.some(l => l.courseId === courseId && l.moduleId === null);
            state.modulesList = [...modules];
            if (hasNoModuleLessons) {
                state.modulesList.push({ id: 'NO_MODULE', title: 'Без модуля', courseId });
            }
        }

        async function onModuleChange(moduleId) {
            state.selectedLessonId = null;
            state.selectedTaskId = null;
            state.lessonsList = [];
            state.tasksList = [];
            state.taskScoreMin = null;
            state.taskScoreMax = null;
            if (!moduleId) return;
            const lessonsData = await fetchAllLessons();
            const allLessons = Object.values(lessonsData.byModuleId).flat();
            if (moduleId === 'NO_MODULE') {
                state.lessonsList = allLessons.filter(l => l.courseId === state.selectedCourseId && l.moduleId === null);
            } else {
                state.lessonsList = lessonsData.byModuleId[moduleId] || [];
            }
        }

        async function onLessonChange(lessonId) {
            state.selectedTaskId = null;
            state.tasksList = [];
            state.taskScoreMin = null;
            state.taskScoreMax = null;
            if (!lessonId) return;
            state.loadingTasks = true;
            try {
                const r = await api.get('/api/v1/tasks');
                state.tasksList = r.data.filter(t => t.lessonId === lessonId);
            } catch (err) {
                state.loadError = 'Не удалось загрузить список заданий';
            } finally {
                state.loadingTasks = false;
            }
        }

        function onTaskChange(taskId) {
            const task = state.tasksList.find(t => String(t.id) === String(taskId));
            state.taskScoreMin = task ? task.scoreMin : null;
            state.taskScoreMax = task ? task.scoreMax : null;
        }

        function onStudentChange(studentId) {
            state.selectedStudentId = studentId || null;
            state.selectedCourseId = null;
            state.selectedModuleId = null;
            state.selectedLessonId = null;
            state.selectedTaskId = null;
            state.score = '';
            state.taskScoreMin = null;
            state.taskScoreMax = null;
            state.modulesList = [];
            state.lessonsList = [];
            state.tasksList = [];
            state.scoreError = null;
            state.alertMsg = null;
        }

        async function resolveLesson(lessonId) {
            try {
                const r = await api.get(`/api/v1/lessons/${lessonId}`);
                const { courseId, moduleId } = r.data;
                state.selectedCourseId = courseId;
                await onCourseChange(courseId);
                state.selectedModuleId = moduleId === null ? 'NO_MODULE' : moduleId;
                await onModuleChange(state.selectedModuleId);
                state.selectedLessonId = lessonId;
                await onLessonChange(lessonId);
            } catch (err) {
                // fallback: форма остаётся в режиме ручного выбора
            }
        }

        async function loadInitialData() {
            state.queryLessonId = currentQuery.lessonId ? parseInt(currentQuery.lessonId, 10) : null;
            state.queryStudentId = currentQuery.studentId || null;
            await Promise.all([loadUsers(), loadCourses()]);
            if (state.queryStudentId) {
                state.selectedStudentId = state.queryStudentId;
            }
            if (state.queryLessonId) {
                await resolveLesson(state.queryLessonId);
            }
        }

        async function onSave() {
            if (!state.selectedStudentId || !state.selectedCourseId || !state.selectedModuleId ||
                !state.selectedLessonId || !state.selectedTaskId || state.score === '') {
                state.scoreError = 'Заполните все поля';
                return;
            }
            state.isSaving = true;
            state.scoreError = null;
            state.alertMsg = null;
            try {
                const execR = await api.get('/api/v1/task-executions');
                const existing = execR.data.find(
                    e => e.userId === state.selectedStudentId &&
                         String(e.taskId) === String(state.selectedTaskId)
                );
                const scoreInt = parseInt(state.score, 10);
                if (existing == null) {
                    await api.post('/api/v1/task-executions', {
                        userId: state.selectedStudentId,
                        taskId: state.selectedTaskId,
                        score: scoreInt,
                    });
                } else {
                    await api.patch(`/api/v1/task-executions/${existing.id}`, {
                        score: scoreInt,
                    });
                }
                state.savedSuccessfully = true;
                state.alertMsg = 'Оценка сохранена';
                state.alertType = 'success';
            } catch (err) {
                if (err.response?.status === 422) {
                    state.scoreError = err.response.data.errors?.score?.[0] ?? 'Некорректное значение';
                } else if (err.response?.status === 403) {
                    state.alertMsg = 'Недостаточно прав для выполнения действия';
                    state.alertType = 'danger';
                } else {
                    state.alertMsg = 'Не удалось сохранить, попробуйте ещё раз';
                    state.alertType = 'danger';
                }
            } finally {
                state.isSaving = false;
            }
        }

        function goBack() {
            const sid = state.selectedStudentId;
            window.location.hash = sid
                ? '#/courses?studentId=' + encodeURIComponent(sid)
                : '#/courses';
        }

        Vue.onMounted(() => loadInitialData());

        return { state, onCourseChange, onModuleChange, onLessonChange, onTaskChange, onStudentChange, onSave, goBack };
    },
    template: `
        <div>
            <nav class="navbar navbar-light bg-light px-3">
                <span class="navbar-brand">Выставить оценку</span>
                <button type="button" class="btn btn-outline-secondary btn-sm" @click="goBack">← Назад к дереву</button>
            </nav>
            <div class="container py-4" style="max-width: 600px;">
                <div v-if="state.loadError" class="alert alert-warning mb-3">{{ state.loadError }}</div>
                <div v-if="state.alertMsg" :class="'alert alert-' + state.alertType + ' mb-3'">{{ state.alertMsg }}</div>
                <form @submit.prevent="onSave">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Студент</label>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select"
                                    v-model="state.selectedStudentId"
                                    @change="onStudentChange(state.selectedStudentId)"
                                    :disabled="state.loadingUsers">
                                <option :value="null" disabled>— Выберите студента —</option>
                                <option v-for="u in state.usersList" :key="u.id" :value="u.id">{{ u.email }}</option>
                            </select>
                            <span v-if="state.loadingUsers" class="spinner-border spinner-border-sm flex-shrink-0" role="status"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Курс</label>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select"
                                    v-model="state.selectedCourseId"
                                    @change="onCourseChange(state.selectedCourseId)"
                                    :disabled="!state.selectedStudentId || state.loadingCourses">
                                <option :value="null" disabled>— Выберите курс —</option>
                                <option v-for="c in state.coursesList" :key="c.id" :value="c.id">{{ c.title }}</option>
                            </select>
                            <span v-if="state.loadingCourses" class="spinner-border spinner-border-sm flex-shrink-0" role="status"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Модуль</label>
                        <select class="form-select"
                                v-model="state.selectedModuleId"
                                @change="onModuleChange(state.selectedModuleId)"
                                :disabled="!state.selectedCourseId">
                            <option :value="null" disabled>— Выберите модуль —</option>
                            <option v-for="m in state.modulesList" :key="m.id" :value="m.id">{{ m.title }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Занятие</label>
                        <select class="form-select"
                                v-model="state.selectedLessonId"
                                @change="onLessonChange(state.selectedLessonId)"
                                :disabled="!state.selectedModuleId">
                            <option :value="null" disabled>— Выберите занятие —</option>
                            <option v-for="l in state.lessonsList" :key="l.id" :value="l.id">{{ l.title }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Задание</label>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select"
                                    v-model="state.selectedTaskId"
                                    @change="onTaskChange(state.selectedTaskId)"
                                    :disabled="!state.selectedLessonId || state.loadingTasks">
                                <option :value="null" disabled>— Выберите задание —</option>
                                <option v-if="state.selectedLessonId && !state.loadingTasks && state.tasksList.length === 0"
                                        disabled :value="null">— нет заданий для этого занятия —</option>
                                <option v-for="t in state.tasksList" :key="t.id" :value="t.id">{{ t.title }}</option>
                            </select>
                            <span v-if="state.loadingTasks" class="spinner-border spinner-border-sm flex-shrink-0" role="status"></span>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Балл</label>
                        <input type="number" step="1" min="0"
                               :class="['form-control', { 'is-invalid': state.scoreError }]"
                               v-model="state.score"
                               :disabled="!state.selectedTaskId || state.savedSuccessfully"
                               placeholder="Введите балл">
                        <div class="form-text"
                             v-if="state.selectedTaskId && state.taskScoreMin !== null && state.taskScoreMax !== null">
                            Допустимый диапазон: {{ state.taskScoreMin }} – {{ state.taskScoreMax }}
                        </div>
                        <div class="invalid-feedback d-block" v-if="state.scoreError">{{ state.scoreError }}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"
                                :disabled="state.isSaving"
                                v-if="!state.savedSuccessfully">
                            <span v-if="state.isSaving">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Сохранение…
                            </span>
                            <span v-else>Сохранить</span>
                        </button>
                        <button type="button" class="btn btn-success" v-if="state.savedSuccessfully" @click="goBack">
                            Вернуться к дереву
                        </button>
                        <button type="button" class="btn btn-outline-secondary" v-if="!state.savedSuccessfully" @click="goBack">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `,
};

// ── Инициализация и монтирование ──────────────────────────────────────────────

loadAuthFromStorage();
applyRouteGuard();

// Watcher на изменения токена — реагирует на login/logout/401 (US2)
Vue.watch(() => authState.token, () => applyRouteGuard());

const App = {
    setup() {
        return { authState, currentRoute };
    },
    template: `
        <login-page v-if="currentRoute === 'login'"></login-page>
        <courses-page v-else-if="currentRoute === 'courses'"></courses-page>
        <score-form-page v-else-if="currentRoute === 'score-form'"></score-form-page>
    `,
};

Vue.createApp(App)
    .component('login-page', LoginPage)
    .component('lesson-row', LessonRow)
    .component('module-row', ModuleRow)
    .component('course-row', CourseRow)
    .component('courses-page', CoursesPage)
    .component('score-form-page', ScoreFormPage)
    .mount('#app');
