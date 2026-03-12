<template>
    <div class="container py-5" style="max-width: 720px;">
        <h1 class="mb-4">Pontszámító Kalkulátor</h1>

        <div class="card mb-4">
            <div class="card-header fw-semibold">Választott szak</div>
            <div class="card-body">
                <select v-model="selectedCourse" class="form-select" @change="onCourseChange">
                    <option value="">Válassz szakot...</option>
                    <option v-for="course in courseList" :key="course.label" :value="course">{{ course.label }}</option>
                </select>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-semibold">Érettségi eredmények</div>
            <div class="card-body">
                <div v-for="(item, index) in form.examResults" :key="index" class="row g-2 mb-2">
                    <div class="col">
                        <select v-model="item.nev" class="form-select">
                            <option value="">Tantárgy...</option>
                            <option v-for="subject in availableSubjects(index)" :key="subject" :value="subject">{{ subject }}</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select v-model="item.tipus" class="form-select">
                            <option value="közép">Közép</option>
                            <option value="emelt">Emelt</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <div class="input-group" style="width: 110px;">
                            <input v-model="item.eredmeny" type="number" min="0" max="100" class="form-control" @input="clampScore(item)" />
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-danger" @click="removeExamResult(index)">✕</button>
                    </div>
                </div>
                <button class="btn btn-outline-primary btn-sm mt-1" @click="addExamResult" :disabled="allSubjectsSelected">+ Tantárgy hozzáadása</button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-semibold">Nyelvvizsga</div>
            <div class="card-body">
                <div v-for="(item, index) in form.bonusPoints" :key="index" class="row g-2 mb-2">
                    <div class="col-auto">
                        <select v-model="item.tipus" class="form-select">
                            <option value="B2">B2</option>
                            <option value="C1">C1</option>
                        </select>
                    </div>
                    <div class="col">
                        <select v-model="item.nyelv" class="form-select">
                            <option value="">Válassz nyelvet...</option>
                            <option v-for="language in availableLanguages(index)" :key="language" :value="language">{{ language }}</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-danger" @click="removeBonusPoint(index)">✕</button>
                    </div>
                </div>
                <button class="btn btn-outline-primary btn-sm mt-1" @click="addBonusPoint" :disabled="allLanguagesSelected">+ Nyelvvizsga hozzáadása</button>
            </div>
        </div>

        <button class="btn btn-primary w-100" @click="submit" :disabled="loading || !canSubmit">
            {{ loading ? 'Számítás...' : 'Pontszám kiszámítása' }}
        </button>

        <div v-if="result !== null" :class="['alert', 'mt-4', resultType === 'success' ? 'alert-success' : 'alert-danger']">
            {{ result }}
        </div>
    </div>
</template>

<script setup>
    import { ref, reactive, computed } from 'vue';

    const loading = ref(false);
    const result = ref(null);
    const resultType = ref('success');

    const courseList = [
        { label: 'ELTE IK - Programtervező informatikus', university: 'ELTE', faculty: 'IK', course: 'Programtervező informatikus' },
        { label: 'PPKE BTK - Anglisztika', university: 'PPKE', faculty: 'BTK', course: 'Anglisztika' },
    ];

    const subjects = [
        'magyar nyelv és irodalom',
        'történelem',
        'matematika',
        'angol nyelv',
        'német nyelv',
        'francia nyelv',
        'olasz nyelv',
        'orosz nyelv',
        'spanyol nyelv',
        'biológia',
        'fizika',
        'informatika',
        'kémia',
    ];

    const languages = [
        'angol',
        'német',
        'francia',
        'olasz',
        'orosz',
        'spanyol',
    ];

    const selectedCourse = ref('');

    const form = reactive({
        selectedCourse: { university: '', faculty: '', course: '' },
        examResults: [{ nev: '', tipus: 'közép', eredmeny: '' }],
        bonusPoints: [],
    });

    const selectedSubjects = computed(() => form.examResults.map(item => item.nev));
    const selectedLanguages = computed(() => form.bonusPoints.map(item => item.nyelv));

    const allSubjectsSelected = computed(() => form.examResults.length >= subjects.length);
    const allLanguagesSelected = computed(() => form.bonusPoints.length >= languages.length);

    const canSubmit = computed(() => {
        return form.selectedCourse.course !== '' &&
            form.examResults.length > 0 &&
            form.examResults.every(item => item.nev !== '' && item.eredmeny !== '');
    });

    function availableSubjects(currentIndex) {
        return subjects.filter(s => !selectedSubjects.value.includes(s) || form.examResults[currentIndex].nev === s);
    }

    function availableLanguages(currentIndex) {
        return languages.filter(l => !selectedLanguages.value.includes(l) || form.bonusPoints[currentIndex].nyelv === l);
    }

    function onCourseChange() {
        if (selectedCourse.value) {
            form.selectedCourse.university = selectedCourse.value.university;
            form.selectedCourse.faculty = selectedCourse.value.faculty;
            form.selectedCourse.course = selectedCourse.value.course;
        }
    }

    function clampScore(item) {
        if (item.eredmeny < 0) item.eredmeny = 0;
        if (item.eredmeny > 100) item.eredmeny = 100;
    }

    function addExamResult() {
        form.examResults.push({ nev: '', tipus: 'közép', eredmeny: '' });
    }

    function removeExamResult(index) {
        form.examResults.splice(index, 1);
    }

    function addBonusPoint() {
        form.bonusPoints.push({ kategoria: 'Nyelvvizsga', tipus: 'B2', nyelv: '' });
    }

    function removeBonusPoint(index) {
        form.bonusPoints.splice(index, 1);
    }

    async function submit() {
        loading.value = true;
        result.value = null;

        const payload = {
            'valasztott-szak': {
                egyetem: form.selectedCourse.university,
                kar: form.selectedCourse.faculty,
                szak: form.selectedCourse.course,
            },
            'erettsegi-eredmenyek': form.examResults.map(item => ({
                ...item,
                eredmeny: item.eredmeny + '%',
            })),
            'tobbletpontok': form.bonusPoints,
        };

        try {
            const response = await fetch('/api/calculate-points', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                resultType.value = 'error';

                switch (response.status) {
                    case 422:
                        result.value = data.message ?? 'Hiba történt a pontszámítás során.';
                        break;
                    case 400:
                        result.value = 'Érvénytelen adatok. Kérjük, ellenőrizd a megadott információkat.';
                        break;
                    default:
                        result.value = 'Hiba történt. Kérjük, próbáld újra később.';
                }
            } else {
                resultType.value = 'success';
                result.value = `Elért pontszám: ${data.points} pont`;
            }
        } catch (e) {
            resultType.value = 'error';
            result.value = 'Hiba történt a kérés során.';
        } finally {
            loading.value = false;
        }
    }
</script>