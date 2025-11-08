# Documentación de Cambios en la API

Este documento describe los cambios arquitectónicos realizados en la API, centrados en la modernización de la serialización de datos y el mapeo de entidades.

## 1. Refactorización General: Eliminación de `toArray()`

Se ha eliminado el método `toArray()` de las siguientes entidades del dominio:

- `Account`
- `Expense`
- `ExpenseType`
- `User`
- `ResidentUnit`

**Motivo**: Este método acoplaba la lógica de negocio del dominio con su representación en formato de array, violando el principio de Responsabilidad Única (SRP). Las entidades del dominio no deben tener conocimiento sobre cómo serán presentadas a las capas externas.

## 2. Introducción del Componente Serializer de Symfony

Para reemplazar la funcionalidad de `toArray()`, se ha adoptado el componente **Symfony Serializer**. Este componente se convierte en la herramienta estándar para transformar objetos de dominio en arrays antes de ser devueltos en las respuestas JSON de la API.

Todos los controladores y manejadores de consultas (`QueryHandler`) que devuelven datos de entidades ahora inyectan `SerializerInterface` para realizar esta tarea.

## 3. Creación de Normalizers

Se ha creado una nueva capa de `Normalizers` dentro de la infraestructura de cada contexto. Estas clases son responsables de la lógica de conversión de una entidad específica a un array.

Los normalizadores creados son:

- `AccountNormalizer`
- `ExpenseNormalizer`
- `ExpenseTypeNormalizer`
- `UserNormalizer`
- `ResidentUnitNormalizer`

Estos servicios están registrados en los ficheros `services.yaml` de sus respectivos contextos con la etiqueta `serializer.normalizer`, lo que permite que el serializador de Symfony los descubra y utilice automáticamente.

**Ejemplo de `ExpenseNormalizer`:**
```php
class ExpenseNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            'id' => $object->id(),
            // ... otras propiedades
            'type' => $this->serializer->normalize($object->type()),
            'account' => $this->serializer->normalize($object->account()),
        ];
    }
    // ...
}
```
Como se puede observar, los normalizadores pueden reutilizar el serializador principal para manejar relaciones anidadas, promoviendo la reutilización de código.

## 4. Impacto en los Endpoints

Los controladores y manejadores de consultas ahora tienen un aspecto más limpio y delegan la serialización:

**Antes:**
```php
// En un QueryHandler
return $expense->toArray();
```

**Ahora:**
```php
// En un QueryHandler (con SerializerInterface inyectado)
return $this->serializer->normalize($expense);
```

Este cambio asegura que la capa de aplicación y la de infraestructura se encargan de la presentación de los datos, no el dominio.

## 5. Refactorización del Mapeo de Doctrine

Se ha eliminado el uso de atributos de Doctrine (`#[ORM\Entity]`, `#[ORM\Column]`, etc.) de las siguientes entidades:

- `Expense`
- `ExpenseType`

**Motivo**: El uso de atributos en las entidades del dominio las acopla a la capa de persistencia, lo cual es un antipatrón en arquitecturas limpias. La configuración de mapeo se ha consolidado en los ficheros `*.orm.xml` correspondientes dentro de la infraestructura de cada contexto, que es el estándar del proyecto.
