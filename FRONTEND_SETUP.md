# Frontend Setup Guide (Nuxt 3)

Đây là hướng dẫn để setup frontend Nuxt 3 cho hệ thống Booking Sport.

## 🚀 Quick Start

```bash
cd ../booking_sport_frontend

# Install dependencies
npm install

# Add required packages
npm install @nuxtjs/tailwindcss @pinia/nuxt @vueuse/nuxt axios

# Start development server
npm run dev
```

## 📦 Required Packages

```bash
# UI & Styling
npm install @nuxtjs/tailwindcss @headlessui/vue @heroicons/vue

# State Management
npm install @pinia/nuxt

# HTTP Client & Utils
npm install @vueuse/nuxt axios

# Authentication
npm install @sidebase/nuxt-auth

# Maps (for venue location)
npm install vue3-google-map

# Form Validation
npm install @vee-validate/nuxt vee-validate @vee-validate/rules

# Date/Time
npm install dayjs

# Notifications
npm install vue-toastification
```

## 🔧 Nuxt Configuration

Update `nuxt.config.ts`:

```typescript
export default defineNuxtConfig({
    devtools: { enabled: true },

    modules: [
        "@nuxtjs/tailwindcss",
        "@pinia/nuxt",
        "@vueuse/nuxt",
        "@vee-validate/nuxt",
        "@sidebase/nuxt-auth",
    ],

    css: ["~/assets/css/main.css"],

    runtimeConfig: {
        public: {
            apiBase:
                process.env.NUXT_PUBLIC_API_BASE ||
                "http://localhost:8000/api/v1",
            appName: "Booking Sport",
            googleMapsKey: process.env.NUXT_PUBLIC_GOOGLE_MAPS_KEY || "",
        },
    },

    auth: {
        baseURL: process.env.AUTH_ORIGIN,
        provider: {
            type: "authjs",
        },
    },

    ssr: false, // SPA mode for better compatibility with Sanctum
});
```

## 🗂️ Recommended Folder Structure

```
booking_sport_frontend/
├── components/
│   ├── Auth/
│   │   ├── LoginForm.vue
│   │   ├── RegisterForm.vue
│   │   └── ProfileForm.vue
│   ├── Venue/
│   │   ├── VenueCard.vue
│   │   ├── VenueList.vue
│   │   └── VenueMap.vue
│   ├── Court/
│   │   ├── CourtCard.vue
│   │   └── CourtAvailability.vue
│   ├── Booking/
│   │   ├── BookingForm.vue
│   │   ├── BookingCard.vue
│   │   └── BookingHistory.vue
│   └── UI/
│       ├── Button.vue
│       ├── Input.vue
│       ├── Modal.vue
│       └── Loading.vue
├── pages/
│   ├── index.vue                    # Home page
│   ├── auth/
│   │   ├── login.vue
│   │   ├── register.vue
│   │   └── profile.vue
│   ├── venues/
│   │   ├── index.vue                # Venue listing
│   │   └── [id].vue                 # Venue details
│   ├── courts/
│   │   └── [id].vue                 # Court details & booking
│   ├── bookings/
│   │   ├── index.vue                # My bookings
│   │   └── [id].vue                 # Booking details
│   └── dashboard/
│       ├── index.vue                # Owner dashboard
│       ├── venues.vue               # Manage venues
│       └── bookings.vue             # Manage bookings
├── stores/
│   ├── auth.ts                      # Authentication store
│   ├── venue.ts                     # Venue management
│   ├── booking.ts                   # Booking management
│   └── ui.ts                        # UI state
├── composables/
│   ├── useAuth.ts                   # Auth composable
│   ├── useApi.ts                    # API composable
│   └── useBooking.ts                # Booking composable
├── middleware/
│   ├── auth.ts                      # Auth middleware
│   ├── guest.ts                     # Guest middleware
│   └── owner.ts                     # Owner middleware
└── types/
    ├── auth.ts                      # Auth types
    ├── venue.ts                     # Venue types
    └── booking.ts                   # Booking types
```

## 🔑 Authentication Setup

### 1. Sanctum SPA Configuration

Create `plugins/axios.client.ts`:

```typescript
export default defineNuxtPlugin(() => {
    const config = useRuntimeConfig();

    // Set default axios config for Sanctum SPA
    $fetch.create({
        baseURL: config.public.apiBase,
        credentials: "include",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    });
});
```

### 2. Auth Store (Pinia)

Create `stores/auth.ts`:

```typescript
export const useAuthStore = defineStore("auth", () => {
    const user = ref(null);
    const isAuthenticated = computed(() => !!user.value);

    const login = async (credentials: LoginCredentials) => {
        // Get CSRF cookie first
        await $fetch("/sanctum/csrf-cookie", {
            baseURL: "http://localhost:8000",
        });

        // Login
        const response = await $fetch("/auth/login", {
            method: "POST",
            body: credentials,
        });

        user.value = response.user;
        await navigateTo("/dashboard");
    };

    const logout = async () => {
        await $fetch("/auth/logout", { method: "POST" });
        user.value = null;
        await navigateTo("/auth/login");
    };

    const fetchUser = async () => {
        try {
            user.value = await $fetch("/auth/me");
        } catch (error) {
            user.value = null;
        }
    };

    return {
        user: readonly(user),
        isAuthenticated,
        login,
        logout,
        fetchUser,
    };
});
```

## 🎨 UI Components

### Key Components to Build:

1. **VenueCard.vue** - Display venue information
2. **CourtAvailability.vue** - Show available time slots
3. **BookingForm.vue** - Booking creation form
4. **VenueMap.vue** - Google Maps integration
5. **DateTimePicker.vue** - Date and time selection
6. **PaymentForm.vue** - Payment processing form

## 🗺️ Pages Structure

### 1. Home Page (`pages/index.vue`)

-   Hero section
-   Featured venues
-   Search functionality
-   Popular sports

### 2. Venue Listing (`pages/venues/index.vue`)

-   Search and filters
-   Map view toggle
-   Venue cards grid
-   Pagination

### 3. Venue Details (`pages/venues/[id].vue`)

-   Venue information
-   Court listing
-   Availability calendar
-   Reviews and ratings

### 4. Court Booking (`pages/courts/[id].vue`)

-   Court details
-   Time slot picker
-   Booking form
-   Payment integration

### 5. Dashboard (`pages/dashboard/index.vue`)

-   Owner dashboard
-   Booking statistics
-   Revenue charts
-   Recent activity

## 🔒 Middleware

### Auth Middleware (`middleware/auth.ts`)

```typescript
export default defineNuxtRouteMiddleware((to, from) => {
    const { isAuthenticated } = useAuthStore();

    if (!isAuthenticated) {
        return navigateTo("/auth/login");
    }
});
```

### Owner Middleware (`middleware/owner.ts`)

```typescript
export default defineNuxtRouteMiddleware((to, from) => {
    const { user } = useAuthStore();

    if (!user?.roles?.includes("owner") && !user?.roles?.includes("admin")) {
        throw createError({
            statusCode: 403,
            statusMessage: "Access Denied",
        });
    }
});
```

## 🎯 API Integration

### API Composable (`composables/useApi.ts`)

```typescript
export const useApi = () => {
    const config = useRuntimeConfig();

    const api = $fetch.create({
        baseURL: config.public.apiBase,
        credentials: "include",
    });

    return {
        // Auth
        login: (credentials: LoginCredentials) =>
            api("/auth/login", { method: "POST", body: credentials }),
        register: (data: RegisterData) =>
            api("/auth/register", { method: "POST", body: data }),
        logout: () => api("/auth/logout", { method: "POST" }),

        // Venues
        getVenues: (params?: VenueFilters) => api("/venues", { params }),
        getVenue: (id: string) => api(`/venues/${id}`),

        // Bookings
        createBooking: (data: CreateBookingData) =>
            api("/bookings", { method: "POST", body: data }),
        getMyBookings: () => api("/me/bookings"),

        // Courts
        getCourtAvailability: (courtId: string, date: string) =>
            api(`/courts/${courtId}/availability?date=${date}`),
    };
};
```

## 🚀 Getting Started

1. **Setup dependencies**:

```bash
cd booking_sport_frontend
npm install
```

2. **Configure environment**:

```bash
# .env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api/v1
NUXT_PUBLIC_GOOGLE_MAPS_KEY=your_google_maps_key
```

3. **Start development**:

```bash
npm run dev
```

4. **Build for production**:

```bash
npm run build
npm run preview
```

## 📱 Mobile Responsiveness

Ensure all components are mobile-first:

-   Use Tailwind CSS responsive utilities
-   Implement touch-friendly interactions
-   Optimize for mobile performance
-   Test on various screen sizes

## 🔔 Notifications

Integrate push notifications:

-   Service worker for PWA
-   FCM integration
-   Real-time booking updates
-   Reminder notifications

---

**Next Steps**: Start with basic authentication and venue listing, then gradually add booking functionality and advanced features.
